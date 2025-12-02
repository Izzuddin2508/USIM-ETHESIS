# System Architecture & Data Flow Diagrams

## 1. Overall System Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                      THESIS MANAGEMENT SYSTEM                       │
└────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐
│  Student Dashboard   │  (Dashboard.php)
│  - Submit Thesis     │
│  - Track Progress    │
└──────────┬───────────┘
           │
           │ File Upload + AJAX Request
           ▼
┌──────────────────────┐
│  PHP Middleware      │  (similarity_checker.php)
│  - Receive file      │
│  - Scan uploads/     │
│  - Call Python API   │
└──────────┬───────────┘
           │ HTTP POST + Thesis List
           ▼
┌──────────────────────────────────────────┐
│  FLASK API (simple_app.py) ✨ FIXED     │
│  - Extract text from files               │
│  - Calculate similarity                  │
│  - Return percentage                     │
└──────────┬───────────────────────────────┘
           │ JSON Response (Similarity %)
           ▼
┌──────────────────────┐
│  Student Dashboard   │
│  Display Results     │
│  ✅ 45.67% Match     │
└──────────────────────┘

         │
         │ Store thesis
         ▼
┌──────────────────────────────────────────┐
│  Database (MySQL) + File Storage         │
│  - Thesis records (thesis table)         │
│  - Files (uploads/thesis/*.pdf)          │
└──────────────────────────────────────────┘
```

## 2. File Path Resolution Flow

```
INPUT: File path from PHP
  │
  └─→ Is it absolute path?
      ├─→ YES: Use as-is (normalized)
      │
      └─→ NO: Extract filename
          └─→ Join with BASE_UPLOAD_PATH
              └─→ Resolve using os.path.join()
                  └─→ Normalize path separators

RESULT: Fully qualified file path
  │
  └─→ Does file exist?
      ├─→ YES: Read file, extract text
      │
      └─→ NO: Try original path as fallback
          ├─→ YES: Read file
          │
          └─→ NO: Log error, skip file

RETURN: Extracted text or empty string
```

## 3. Similarity Checking Process

```
┌─────────────────────────────────────────┐
│ Student Submits New Thesis              │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Extract Text from Uploaded File         │
│ └─→ Handle PDF, DOCX, TXT, DOC          │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Get List of Existing Thesis Files       │
│ └─→ From uploads/thesis/ folder         │
└────────────┬────────────────────────────┘
             │
             ├─→ No existing files?
             │   └─→ Return 0% similarity ✅
             │
             └─→ Process each existing file:
                 │
                 ├─→ Extract Text
                 │
                 ├─→ Clean Text
                 │   └─→ Remove special chars
                 │   └─→ Convert to lowercase
                 │   └─→ Normalize whitespace
                 │
                 ├─→ Calculate Similarity
                 │   ├─→ AI Method (80% weight)
                 │   │   └─→ Sentence embeddings
                 │   │   └─→ Cosine similarity
                 │   │
                 │   └─→ Word Method (20% weight)
                 │       └─→ Word intersection
                 │       └─→ Word union
                 │       └─→ Jaccard similarity
                 │
                 └─→ Track maximum similarity
                     └─→ Record which file matches
             │
             ▼
┌─────────────────────────────────────────┐
│ Return Results                          │
│ - max_similarity: XX.XX%                │
│ - similar_file: filename                │
│ - message: Human-readable text          │
│ - can_submit: true (always)             │
└─────────────────────────────────────────┘
```

## 4. File Access - Before vs After

### BEFORE (Failed)
```
File Path: uploads/thesis/123_456.pdf
    │
    ├─→ Try to open directly
    │   └─→ ❌ FileNotFoundError
    │
    └─→ No fallback
        └─→ ❌ Return empty text
            └─→ ❌ Similarity check fails
                └─→ ❌ API returns error
```

### AFTER (Fixed) ✅
```
File Path: uploads/thesis/123_456.pdf
    │
    ├─→ Is absolute?
    │   └─→ NO
    │
    ├─→ Extract filename: "123_456.pdf"
    │
    ├─→ Join with BASE_UPLOAD_PATH
    │   └─→ c:\laragon\fyp\uploads\thesis\123_456.pdf
    │
    ├─→ File exists?
    │   └─→ YES ✅
    │
    ├─→ Extract text ✅
    │   ├─→ Detect format: PDF
    │   ├─→ Read with PyPDF2
    │   └─→ Return extracted text
    │
    └─→ Continue processing ✅
```

## 5. Error Handling Flow

```
Read File
    │
    ├─→ Success?
    │   └─→ ✅ Return text
    │
    └─→ Failed?
        │
        ├─→ Log: "Error reading file: [reason]"
        │
        ├─→ Check alternate path
        │   ├─→ Found? → Use it ✅
        │   └─→ Not found? → Continue ↓
        │
        ├─→ Try fallback methods
        │   ├─→ Different encoding
        │   ├─→ Different parser
        │   └─→ Still failed? → Empty string
        │
        └─→ Return to main process
            └─→ Continue with next file
                └─→ Graceful degradation ✅
```

## 6. Data Format Specifications

### Request to Flask API
```
POST /api/check_similarity
Content-Type: multipart/form-data

file: <Binary File Content>
existing_files: [
  {
    "filename": "123_1234567890.pdf",
    "path": "uploads/thesis/123_1234567890.pdf"
  },
  {
    "filename": "456_1234567890.docx",
    "path": "uploads/thesis/456_1234567890.docx"
  }
]
```

### Response from Flask API
```json
{
  "max_similarity": 45.67,
  "message": "✅ Similarity check completed! Highest similarity: 45.67% (with 123_1234567890.pdf)",
  "similar_file": "123_1234567890.pdf",
  "can_submit": true
}
```

## 7. Directory Structure

```
c:\laragon\fyp\
│
├── index.php
├── Dashboard.php                ← Student dashboard (calls API)
├── similarity_checker.php       ← PHP middleware
│
├── web-test\
│   ├── simple_app.py           ← Flask API ✨ FIXED
│   ├── requirements.txt         ← Python dependencies
│   └── verify_api.py            ← Verification script
│
├── uploads\
│   └── thesis\                  ← Thesis files stored here
│       ├── 123_1234567890.pdf
│       ├── 456_1234567890.docx
│       └── 789_1234567890.txt
│
└── [Documentation Files]
    ├── QUICKSTART.md
    ├── FIX_SUMMARY.md
    ├── API_FIX_README.md
    └── IMPLEMENTATION_COMPLETE.md
```

## 8. Processing Timeline

```
t=0s     Student selects thesis file
         └─→ handleFileChange() triggered

t=0.5s   checkSimilarity() called
         └─→ Show loading indicator

t=0.5s   AJAX POST to similarity_checker.php
         └─→ PHP scans uploads/thesis/
         └─→ PHP calls Flask API

t=0.6s   Flask API receives request
         └─→ Load existing files list

t=0.7s   Extract text from uploaded file
         └─→ Detect format (PDF/DOCX/etc)
         └─→ Extract content

t=1.0s   Process each existing file
         ├─→ Resolve file path
         ├─→ Extract text
         ├─→ Calculate similarity
         └─→ Track maximum

t=2.5s   Return results to PHP
         └─→ JSON response

t=2.6s   PHP forwards to Dashboard
         └─→ JavaScript receives result

t=2.7s   Update UI with similarity %
         └─→ Enable submit button
         └─→ Show success message

t=2.8s   Student sees: "Similarity: 45.67% ✅"
         └─→ Can submit thesis
```

## 9. Similarity Calculation Details

```
Input: Two thesis texts

Step 1: Clean text
└─→ Convert to lowercase
└─→ Remove special characters
└─→ Normalize whitespace

Step 2: Calculate embeddings (AI Method)
└─→ Sentence transformer model
└─→ Creates vector representation
└─→ Captures semantic meaning

Step 3: Calculate cosine similarity
└─→ Cosine distance between vectors
└─→ Range: 0.0 to 1.0
└─→ Represents semantic similarity

Step 4: Calculate word similarity (Backup)
└─→ Word intersection / word union
└─→ Jaccard similarity coefficient
└─→ Captures lexical overlap

Step 5: Combine scores
└─→ AI similarity: 80%
└─→ Word similarity: 20%
└─→ Final score = (AI × 0.8) + (Word × 0.2)

Step 6: Convert to percentage
└─→ Multiply by 100
└─→ Round to 2 decimal places
└─→ Display: XX.XX%
```

## 10. Fallback & Recovery Mechanisms

```
Primary Path: Absolute/Resolved Path
    │
    ├─→ Exists? ✅
    │   └─→ Use it
    │
    └─→ Doesn't exist?
        │
        └─→ Fallback 1: Original path as-is
            ├─→ Exists? ✅
            │   └─→ Use it
            │
            └─→ Doesn't exist?
                │
                └─→ Fallback 2: By filename only
                    ├─→ Search in BASE_UPLOAD_PATH
                    ├─→ Found? ✅
                    │   └─→ Use it
                    │
                    └─→ Not found?
                        │
                        └─→ Log error
                        └─→ Return empty
                        └─→ Continue to next file
                        └─→ No crash ✅
```

---

## Legend

- ✅ = Success / Working
- ❌ = Failure / Not working
- ⚠️ = Warning / Limited
- → = Flow / Process
- ├─ = Branch point
- └─ = End point

---

**Last Updated**: 2025-11-26
**Version**: 2.0 (Fixed)
