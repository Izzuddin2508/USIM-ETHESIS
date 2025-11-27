import os
import re
from flask import Flask, request, jsonify
from flask_cors import CORS
import PyPDF2
from difflib import SequenceMatcher
import glob
import docx
import json
from sentence_transformers import SentenceTransformer, util
from pathlib import Path

app = Flask(__name__)
CORS(app)

# Define the base path for thesis files (relative to the Laravel/PHP project root)
# The Flask app is in /fyp/web-test, so thesis files are in ../uploads/thesis/
BASE_UPLOAD_PATH = os.path.normpath(os.path.join(os.path.dirname(__file__), '..', 'uploads', 'thesis'))

# Initialize the AI model for semantic similarity
print("Loading AI model for similarity checking...")
model = SentenceTransformer('all-MiniLM-L6-v2')
print("AI model loaded successfully!")
print(f"Base upload path: {BASE_UPLOAD_PATH}")

# Function to extract text from uploaded file
def extract_text(file):
    filename = file.filename.lower()
    text = ""

    try:
        if filename.endswith('.pdf'):
            reader = PyPDF2.PdfReader(file)
            for page in reader.pages:
                text += page.extract_text() or ""
        elif filename.endswith('.txt'):
            text = file.read().decode('utf-8', errors='ignore')
        elif filename.endswith('.docx'):
            # For docx files, use python-docx if available
            doc = docx.Document(file)
            text = '\n'.join([paragraph.text for paragraph in doc.paragraphs])
        else:
            text = ""
    except Exception as e:
        print(f"Error extracting text from uploaded file: {e}")
        text = ""
    
    return text.strip()

# Function to extract text from file path
def extract_text_from_path(file_path):
    text = ""
    filename = file_path.lower()
    
    # Resolve the file path - it may be relative or absolute
    if not os.path.isabs(file_path):
        # If relative, resolve it relative to the base upload path
        resolved_path = os.path.normpath(os.path.join(BASE_UPLOAD_PATH, os.path.basename(file_path)))
    else:
        resolved_path = os.path.normpath(file_path)
    
    print(f"Attempting to read file: {file_path}")
    print(f"Resolved path: {resolved_path}")
    
    # Verify the file exists
    if not os.path.exists(resolved_path):
        print(f"File not found at resolved path: {resolved_path}")
        # Try alternate paths
        if os.path.exists(file_path):
            resolved_path = file_path
            print(f"Found at original path: {file_path}")
        else:
            print(f"File not found at any path")
            return ""
    
    try:
        if filename.endswith('.pdf'):
            with open(resolved_path, 'rb') as file:
                reader = PyPDF2.PdfReader(file)
                for page in reader.pages:
                    text += page.extract_text() or ""
        elif filename.endswith('.txt'):
            with open(resolved_path, 'r', encoding='utf-8', errors='ignore') as file:
                text = file.read()
        elif filename.endswith('.docx') or filename.endswith('.doc'):
            try:
                doc = docx.Document(resolved_path)
                text = '\n'.join([paragraph.text for paragraph in doc.paragraphs])
            except Exception as docx_error:
                print(f"Error reading DOCX file: {docx_error}")
                # For .doc files, return empty text as python-docx doesn't support old Word format
                text = ""
    except Exception as e:
        print(f"Error reading file {resolved_path}: {e}")
        text = ""
    
    return text.strip()

def clean_text(text):
    """Clean and normalize text for comparison"""
    # Remove extra whitespace
    text = re.sub(r'\s+', ' ', text)
    # Remove special characters and convert to lowercase
    text = re.sub(r'[^\w\s]', '', text.lower())
    return text.strip()

def calculate_text_similarity(text1, text2):
    """Calculate similarity between two texts using AI-powered semantic similarity"""
    # Clean texts
    clean_text1 = clean_text(text1)
    clean_text2 = clean_text(text2)
    
    if not clean_text1 or not clean_text2:
        return 0
    
    try:
        # Primary method: AI-powered semantic similarity using sentence transformers
        embedding1 = model.encode(clean_text1, convert_to_tensor=True)
        embedding2 = model.encode(clean_text2, convert_to_tensor=True)
        ai_similarity = util.cos_sim(embedding1, embedding2).item()
        
        # Secondary method: Traditional text-based similarity as backup
        sequence_similarity = SequenceMatcher(None, clean_text1, clean_text2).ratio()
        
        # Use AI similarity as primary, fall back to sequence matching if AI fails
        if ai_similarity > 0:
            primary_similarity = ai_similarity
        else:
            primary_similarity = sequence_similarity
            
        # Optional: Word-based similarity for additional context
        words1 = set(clean_text1.split())
        words2 = set(clean_text2.split())
        
        if words1 and words2:
            intersection = len(words1.intersection(words2))
            union = len(words1.union(words2))
            word_similarity = intersection / union if union > 0 else 0
        else:
            word_similarity = 0
        
        # Combine AI similarity (80%) with word similarity (20%) for final score
        final_similarity = (primary_similarity * 0.8 + word_similarity * 0.2)
        
        return final_similarity
        
    except Exception as e:
        print(f"Error in AI similarity calculation, falling back to basic method: {e}")
        # Fallback to basic similarity if AI method fails
        return SequenceMatcher(None, clean_text1, clean_text2).ratio()

@app.route('/api/check_similarity', methods=['POST'])
def check_similarity():
    try:
        if 'file' not in request.files:
            return jsonify({'error': 'No file uploaded'}), 400
        
        uploaded_file = request.files['file']
        if uploaded_file.filename == '':
            return jsonify({'error': 'No file selected'}), 400
        
        # Get existing files data from PHP
        existing_files_json = request.form.get('existing_files', '[]')
        try:
            existing_files = json.loads(existing_files_json)
        except json.JSONDecodeError:
            existing_files = []
        
        # Extract text from uploaded file
        uploaded_text = extract_text(uploaded_file)
        if not uploaded_text:
            return jsonify({'error': 'Could not extract text from uploaded file'}), 400
        
        if not existing_files:
            return jsonify({
                'max_similarity': 0,
                'message': '✅ No existing thesis files found to compare against. You can submit your thesis.',
                'similar_file': None,
                'can_submit': True
            })
        
        max_similarity = 0
        similar_file = None
        
        # Check similarity against each existing file
        for file_info in existing_files:
            file_path = file_info['path']
            filename = file_info['filename']
            
            print(f"Processing existing file: {filename}")
            existing_text = extract_text_from_path(file_path)
            
            if existing_text:
                similarity = calculate_text_similarity(uploaded_text, existing_text)
                similarity_percentage = round(similarity * 100, 2)
                
                print(f"Similarity with {filename}: {similarity_percentage}%")
                
                if similarity_percentage > max_similarity:
                    max_similarity = similarity_percentage
                    similar_file = filename
            else:
                print(f"Could not extract text from {filename}")
        
        # Always allow submission, just show the highest similarity
        message = f"✅ Similarity check completed! Highest similarity: {max_similarity}%"
        if similar_file:
            message += f" (with {similar_file})"
        
        return jsonify({
            'max_similarity': max_similarity,
            'message': message,
            'similar_file': similar_file,
            'can_submit': True  # Always allow submission
        })
        
    except Exception as e:
        print(f"Error in check_similarity: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({'error': f'Server error: {str(e)}'}), 500

@app.route('/health', methods=['GET'])
def health_check():
    # Check if upload directory exists
    upload_dir_exists = os.path.exists(BASE_UPLOAD_PATH)
    return jsonify({
        'status': 'ok', 
        'message': 'Similarity checker API is running',
        'upload_path': BASE_UPLOAD_PATH,
        'upload_dir_exists': upload_dir_exists
    })

if __name__ == '__main__':
    print("Starting AI-Powered Thesis Similarity Checker API...")
    print("API will be available at: http://localhost:5000")
    print("Features: Simple percentage-based similarity analysis")
    app.run(debug=True, host='0.0.0.0', port=5000)