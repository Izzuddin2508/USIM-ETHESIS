import os
import re
from flask import Flask, render_template, request, jsonify
from flask_cors import CORS
from sentence_transformers import SentenceTransformer, util
import PyPDF2
from difflib import SequenceMatcher
import glob

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes
model = SentenceTransformer('all-MiniLM-L6-v2')

# Function to extract text from uploaded file
def extract_text(file):
    filename = file.filename.lower()
    text = ""

    if filename.endswith('.pdf'):
        reader = PyPDF2.PdfReader(file)
        for page in reader.pages:
            text += page.extract_text() or ""
    elif filename.endswith('.txt'):
        text = file.read().decode('utf-8', errors='ignore')
    else:
        text = ""
    return text.strip()

# Function to extract text from file path
def extract_text_from_path(file_path):
    text = ""
    filename = file_path.lower()
    
    try:
        if filename.endswith('.pdf'):
            with open(file_path, 'rb') as file:
                reader = PyPDF2.PdfReader(file)
                for page in reader.pages:
                    text += page.extract_text() or ""
        elif filename.endswith('.txt'):
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as file:
                text = file.read()
        elif filename.endswith(('.doc', '.docx')):
            # For Word documents, we'll skip them for now or you can add python-docx library
            text = ""
    except Exception as e:
        print(f"Error reading file {file_path}: {e}")
        text = ""
    
    return text.strip()

# Function to find similar text segments
def find_similar_segments(text1, text2, min_length=10, similarity_threshold=0.3):
    # Split texts into sentences
    sentences1 = re.split(r'[.!?]+', text1)
    sentences2 = re.split(r'[.!?]+', text2)
    
    # Clean sentences
    sentences1 = [s.strip() for s in sentences1 if len(s.strip()) >= min_length]
    sentences2 = [s.strip() for s in sentences2 if len(s.strip()) >= min_length]
    
    similar_segments = []
    
    for sent1 in sentences1:
        for sent2 in sentences2:
            # Calculate similarity using SequenceMatcher
            similarity = SequenceMatcher(None, sent1.lower(), sent2.lower()).ratio()
            
            if similarity >= similarity_threshold:
                similar_segments.append({
                    'text1': sent1,
                    'text2': sent2,
                    'similarity': round(similarity * 100, 1)
                })
    
    # Sort by similarity score and return top 5 most similar segments
    similar_segments.sort(key=lambda x: x['similarity'], reverse=True)
    return similar_segments[:5]

# Function to find common phrases
def find_common_phrases(text1, text2, min_length=3):
    # Split into words and create n-grams
    words1 = text1.lower().split()
    words2 = text2.lower().split()
    
    common_phrases = []
    
    # Check for common phrases of different lengths
    for length in range(min_length, min(20, min(len(words1), len(words2)))):
        for i in range(len(words1) - length + 1):
            phrase1 = ' '.join(words1[i:i+length])
            
            for j in range(len(words2) - length + 1):
                phrase2 = ' '.join(words2[j:j+length])
                
                if phrase1 == phrase2 and len(phrase1.strip()) > 0:
                    common_phrases.append(phrase1)
                    break
    
    # Remove duplicates and return unique phrases
    return list(set(common_phrases))[:10]

@app.route('/api/check_thesis_similarity', methods=['POST'])
def check_thesis_similarity():
    try:
        if 'file' not in request.files:
            return jsonify({'error': 'No file uploaded'}), 400
        
        uploaded_file = request.files['file']
        if uploaded_file.filename == '':
            return jsonify({'error': 'No file selected'}), 400
        
        # Extract text from uploaded file
        uploaded_text = extract_text(uploaded_file)
        if not uploaded_text:
            return jsonify({'error': 'Could not extract text from uploaded file'}), 400
        
        # Path to existing thesis files
        thesis_folder = '../uploads/thesis/'
        if not os.path.exists(thesis_folder):
            return jsonify({'error': 'Thesis folder not found'}), 404
        
        # Find all thesis files
        thesis_files = []
        for ext in ['*.pdf', '*.txt', '*.doc', '*.docx']:
            thesis_files.extend(glob.glob(os.path.join(thesis_folder, ext)))
        
        if not thesis_files:
            return jsonify({
                'max_similarity': 0,
                'message': 'No existing thesis files found to compare against',
                'similar_files': [],
                'can_submit': True
            })
        
        max_similarity = 0
        similar_files = []
        
        # Check similarity against each existing file
        for file_path in thesis_files:
            existing_text = extract_text_from_path(file_path)
            if existing_text:
                # Calculate similarity using sentence transformers
                embedding1 = model.encode(uploaded_text, convert_to_tensor=True)
                embedding2 = model.encode(existing_text, convert_to_tensor=True)
                similarity = util.cos_sim(embedding1, embedding2).item()
                similarity_percentage = round(similarity * 100, 2)
                
                if similarity_percentage > max_similarity:
                    max_similarity = similarity_percentage
                
                if similarity_percentage > 30:  # Only include files with >30% similarity
                    similar_files.append({
                        'filename': os.path.basename(file_path),
                        'similarity': similarity_percentage
                    })
        
        # Sort similar files by similarity
        similar_files.sort(key=lambda x: x['similarity'], reverse=True)
        
        # Determine if user can submit
        can_submit = max_similarity < 30
        
        # Prepare response message
        if can_submit:
            message = f"✅ Similarity check passed! Maximum similarity: {max_similarity}%. You can submit your thesis."
        else:
            message = f"❌ High similarity detected! Maximum similarity: {max_similarity}%. Please revise your thesis before submission."
        
        return jsonify({
            'max_similarity': max_similarity,
            'message': message,
            'similar_files': similar_files[:5],  # Return top 5 similar files
            'can_submit': can_submit
        })
        
    except Exception as e:
        return jsonify({'error': f'Server error: {str(e)}'}), 500

@app.route('/')
def home():
    return render_template('index.html', result=None)

@app.route('/check', methods=['POST'])
def check_similarity():
    file1 = request.files['file1']
    file2 = request.files['file2']

    if not file1 or not file2:
        return render_template('index.html', message="⚠️ Please upload both files.", result=None)

    text1 = extract_text(file1)
    text2 = extract_text(file2)

    if not text1 or not text2:
        return render_template('index.html', message="❌ Could not extract text from one or both files.", result=None)

    # Calculate overall similarity using sentence transformers
    embedding1 = model.encode(text1, convert_to_tensor=True)
    embedding2 = model.encode(text2, convert_to_tensor=True)
    similarity = util.cos_sim(embedding1, embedding2).item()
    percentage = round(similarity * 100, 2)

    # Find similar segments and common phrases
    similar_segments = find_similar_segments(text1, text2)
    common_phrases = find_common_phrases(text1, text2)
    
    # Debug: Print what we found
    print(f"Found {len(similar_segments)} similar segments")
    print(f"Found {len(common_phrases)} common phrases")
    if similar_segments:
        print("Similar segments:", similar_segments[:2])  # Show first 2
    if common_phrases:
        print("Common phrases:", common_phrases[:3])  # Show first 3

    if similarity > 0.8:
        message = "✅ The documents are very similar."
    elif similarity > 0.5:
        message = "⚠️ The documents are moderately similar."
    else:
        message = "❌ The documents are different."

    return render_template('index.html', 
                         result=percentage, 
                         message=message,
                         similar_segments=similar_segments,
                         common_phrases=common_phrases,
                         file1_name=file1.filename,
                         file2_name=file2.filename)

if __name__ == '__main__':
    app.run(debug=True)
