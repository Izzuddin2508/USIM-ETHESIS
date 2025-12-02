# Turnitin-Style Plagiarism Detection Logic

## Overview
The similarity detection system has been restructured to work like **Turnitin**, a professional plagiarism detection tool. The new logic focuses on detecting actual plagiarism rather than semantic similarity.

## Key Changes

### 1. **Detection Method**
**Old Approach**: Semantic similarity using AI embeddings (SentenceTransformer)
- Detected documents with similar meaning, even if worded differently
- Less effective at catching paraphrased plagiarism
- Required heavy AI model loading

**New Approach**: Turnitin-style n-gram + consecutive matching
- Detects word-for-word plagiarism and paraphrased content
- Analyzes actual word sequences (phrases)
- Finds longest common consecutive text
- Doesn't require AI models - uses efficient string matching

---

## Core Algorithms

### 2. **N-gram Matching** (Phrase Detection)
```python
extract_ngrams(text, n=5)
```
- Extracts 5-word sequences (phrases) from text
- Compares phrases between uploaded and existing documents
- Detects paraphrased plagiarism (same ideas, different wording)
- Only considers meaningful phrases (>10 characters)

**Example**:
- Text 1: "The student submitted their thesis for review"
- Text 2: "He submitted his thesis for review"
- N-gram match: "submitted their thesis for review" vs "submitted his thesis for review"

### 3. **Consecutive Text Matching** (Word-for-Word Detection)
```python
find_longest_common_substring(text1, text2)
```
- Uses Python's `SequenceMatcher` to find matching consecutive sequences
- Detects copy-pasted content and direct quotes without attribution
- Returns longest matching blocks with context
- Minimum match length: 20 characters

**Example**:
- If both documents contain: "The methodology section describes the research approach"
- This exact phrase is flagged as a consecutive match

### 4. **Turnitin Similarity Score Calculation**

The final plagiarism score is calculated as:

```
TURNITIN_SCORE = (Consecutive_Match × 70%) + (Phrase_Match × 30%)
```

**Weighting Logic**:
- **70% Consecutive Matching**: Catches word-for-word plagiarism (primary indicator)
- **30% Phrase Matching**: Catches paraphrased plagiarism (secondary indicator)

**Components**:

| Component | Purpose | Example |
|-----------|---------|---------|
| **Consecutive Match** | % of uploaded text that exactly matches existing document | 45% = 45% of your text matches word-for-word |
| **Phrase Match** | % of your n-grams (5-word phrases) found in existing document | 30% = 30% of your phrases appear in existing work |
| **Word Overlap** | % of unique words shared between documents | Informational only (not in final score) |

---

## Detection Types

### Type 1: Phrase Match (Paraphrased Plagiarism)
```json
{
  "type": "PHRASE_MATCH",
  "similarity": 15.5,
  "match_count": 8,
  "description": "Detected 8 matching phrases covering 15.5% of uploaded document"
}
```
- Flags when 5-word phrases are reused
- Indicates paraphrasing without proper citation
- Threshold: Coverage ≥ 65%

### Type 2: Consecutive Match (Word-for-Word Plagiarism)
```json
{
  "type": "CONSECUTIVE_MATCH",
  "similarity": 22.3,
  "match_length": 145,
  "description": "Found 145 consecutive matching characters"
}
```
- Flags when identical text sequences are found
- Indicates copy-paste without changes
- Threshold: Coverage ≥ 32.5% (65% × 0.5)

---

## How It Works - Step by Step

### Check Similarity Process:

```
1. Upload thesis file (PDF, DOCX, TXT)
   ↓
2. Extract text from uploaded file
   ↓
3. For each existing thesis in database:
   ├─ Extract text from existing file
   ├─ Calculate TURNITIN_SCORE = (Consecutive Match × 0.70) + (Phrase Match × 0.30)
   ├─ Find flagged phrase matches and consecutive sequences
   └─ Store highest similarity score and matched file
   ↓
4. Return results:
   - Overall plagiarism percentage
   - Matched file name
   - List of detected phrase and consecutive matches
   - Generate PDF report with flagged sections
```

---

## Similarity Interpretation

### Score Ranges:

| Score | Interpretation | Action |
|-------|----------------|--------|
| 0-10% | Minimal similarity | ✅ Safe to submit |
| 10-25% | Low similarity (common phrases) | ⚠️ Check flagged sections |
| 25-50% | Moderate similarity (paraphrase detected) | ⚠️ Review and cite properly |
| 50-75% | High similarity (substantial plagiarism) | ❌ Needs revision |
| 75%+ | Very high similarity (direct plagiarism) | ❌ Do not submit |

---

## Key Differences from Previous System

| Aspect | Previous (Semantic) | New (Turnitin-Style) |
|--------|-------------------|----------------------|
| **Detection Focus** | Meaning/concepts | Actual plagiarism (phrases & text) |
| **Method** | AI embeddings | N-gram + string matching |
| **Catches** | Similar ideas | Copy-paste, paraphrasing, reused phrases |
| **Speed** | Slower (AI model load) | Fast (direct string comparison) |
| **Dependencies** | SentenceTransformer, PyTorch | Python standard library (difflib) |
| **Accuracy** | High for semantic similarity | High for plagiarism detection |

---

## Example Output

### Input: Upload thesis with 10% plagiarized content
```
API Response:
{
  "max_similarity": 23.5,
  "message": "PLAGIARISM CHECK completed! Highest similarity: 23.5% (with thesis_2023.pdf)",
  "similar_file": "thesis_2023.pdf",
  "similar_sentences": [
    {
      "type": "CONSECUTIVE_MATCH",
      "similarity": 18.2,
      "description": "Found 156 consecutive matching characters"
    },
    {
      "type": "PHRASE_MATCH",
      "similarity": 5.3,
      "description": "Detected 3 matching phrases covering 5.3%"
    }
  ]
}
```

### Console Output:
```
[SIMILARITY BREAKDOWN]
  Consecutive match: 18.2%
  Phrase match: 5.3%
  Word overlap: 32.1%
  TURNITIN SCORE: 23.5%
```

---

## Technical Details

### Text Normalization
Both texts are cleaned before comparison:
- Extra whitespace removed
- Special characters removed
- Converted to lowercase
- This allows matching despite minor formatting differences

### File Format Support
- **PDF**: Extracts text from all pages using PyPDF2
- **DOCX**: Extracts from paragraphs using python-docx
- **TXT**: Direct text reading
- **DOC**: Not supported (requires microsoft-word library)

### Performance Notes
- Turnitin-style detection is **much faster** than AI-based methods
- Can process large thesis files (100+ pages) in seconds
- No GPU/AI model loading required
- Suitable for production use

---

## Configuration

### Adjustable Parameters:

```python
# File: simple_app.py

# N-gram size (word sequences to match)
find_matching_ngrams(..., ngram_size=5)  # Change to 4, 6, 7, etc.

# Minimum consecutive match length
find_longest_common_substring(..., min_length=20)  # Increase to be stricter

# Phrase match threshold (affects how sensitive detection is)
find_similar_sentences(..., threshold=0.65)  # Range: 0.0-1.0

# Weighting in final score
turnitin_similarity = (consecutive × 0.70) + (phrase × 0.30)  # Adjust weights
```

### Recommended Tuning:
- **For strict detection**: Increase `min_length` to 30+, decrease `threshold` to 0.5
- **For lenient detection**: Decrease `min_length` to 15, increase `threshold` to 0.75
- **For emphasis on paraphrasing**: Change weights to (0.60, 0.40)
- **For emphasis on word-for-word**: Keep current (0.70, 0.30)

---

## Related Files
- **API**: `c:\laragon\fyp\web-test\simple_app.py` - Flask backend with Turnitin logic
- **Middleware**: `c:\laragon\fyp\similarity_checker.php` - PHP bridge to Flask
- **Frontend**: `c:\laragon\fyp\Dashboard.php` - Student submission UI
- **Reports**: PDF generated via `/api/generate_report` endpoint

---

## Testing the System

### Manual Test:
```bash
# 1. Start Flask API
cd C:\laragon\fyp\web-test
python simple_app.py

# 2. Check health
curl http://localhost:5000/health

# 3. Upload a thesis file via Dashboard.php
# View similarity results and PDF report
```

### Expected Behavior:
1. Upload thesis file → Processes in <5 seconds
2. See similarity % based on Turnitin scoring
3. View "View Report" button (if matches found)
4. PDF shows flagged phrases and consecutive matches
