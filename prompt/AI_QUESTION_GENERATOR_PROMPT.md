# AI Question Generator Prompt

## 📝 **Complete Prompt for AI (Copy & Paste Ready)**

```
You are an expert technical assessment question generator. Generate high-quality multiple-choice questions (MCQs) for technical assessments.

## REQUIREMENTS:

### 1. Question Format:
- Each question must have exactly 4 options (A, B, C, D)
- Questions should be clear, concise, and technically accurate
- Difficulty should match the specified level (Beginner/Intermediate/Advanced)
- Questions should test practical knowledge, not just memorization

### 2. Correct Answer Distribution:
- CRITICAL: Correct answers must be evenly distributed across A, B, C, D
- For every 4 questions: 1 should have answer A, 1 should have B, 1 should have C, 1 should have D
- DO NOT make all answers "A" - this is a common mistake
- Randomize which option is correct for each question

### 3. Option Quality:
- All 4 options should be plausible (not obviously wrong)
- Include common mistakes as distractors
- Options should be similar in length when possible
- Avoid "All of the above" or "None of the above" unless necessary

### 4. Output Format:
Generate questions in this EXACT format (JSON):

```json
[
  {
    "question": "What does HTML stand for?",
    "option_a": "Hyper Text Markup Language",
    "option_b": "High Tech Modern Language",
    "option_c": "Home Tool Markup Language",
    "option_d": "Hyperlinks and Text Markup Language",
    "correct_option": "A"
  },
  {
    "question": "Which HTTP method is used to retrieve data?",
    "option_a": "POST",
    "option_b": "GET",
    "option_c": "PUT",
    "option_d": "DELETE",
    "correct_option": "B"
  },
  {
    "question": "What is the default port for HTTP?",
    "option_a": "443",
    "option_b": "8080",
    "option_c": "80",
    "option_d": "3000",
    "correct_option": "C"
  },
  {
    "question": "Which SQL statement is used to extract data?",
    "option_a": "UPDATE",
    "option_b": "DELETE",
    "option_c": "INSERT",
    "option_d": "SELECT",
    "correct_option": "D"
  }
]
```

### 5. Topic Coverage:
Generate questions covering:
- Core concepts and fundamentals
- Syntax and language features
- Best practices and patterns
- Common errors and debugging
- Real-world scenarios
- Performance and optimization (for advanced level)

### 6. Quality Checklist:
- ✓ Question is unambiguous
- ✓ Only ONE correct answer
- ✓ All options are grammatically correct
- ✓ Correct answer is distributed (A, B, C, D rotation)
- ✓ Difficulty matches specified level
- ✓ Question tests understanding, not memorization

## YOUR TASK:

Generate [NUMBER] questions for:
- **Role:** [ROLE_NAME] (e.g., Full Stack Developer, Backend Developer, Python Developer, Flutter Developer, MERN Developer)
- **Level:** [LEVEL] (Beginner / Intermediate / Advanced)
- **Topics:** [SPECIFIC_TOPICS] (e.g., HTML, CSS, JavaScript, React, Node.js, MongoDB, etc.)

## IMPORTANT:
1. Ensure correct_option is evenly distributed: ~25% A, ~25% B, ~25% C, ~25% D
2. Mix question types: conceptual, code-based, output prediction, best practices
3. For Beginner: Focus on basics, syntax, fundamental concepts
4. For Intermediate: Include patterns, frameworks, common scenarios
5. For Advanced: Include optimization, architecture, complex problem-solving

Generate the questions now in the JSON format specified above.
```

---

## 🎯 **Usage Examples**

### Example 1: Full Stack Developer - Beginner
```
[Copy the prompt above and replace:]
- [NUMBER] = 100
- [ROLE_NAME] = Full Stack Developer
- [LEVEL] = Beginner
- [SPECIFIC_TOPICS] = HTML, CSS, JavaScript, DOM, HTTP, Basic SQL, Git basics
```

### Example 2: Backend Developer - Intermediate
```
[Copy the prompt above and replace:]
- [NUMBER] = 100
- [ROLE_NAME] = Backend Developer
- [LEVEL] = Intermediate
- [SPECIFIC_TOPICS] = REST APIs, Node.js, Express.js, Database design, Authentication, Error handling
```

### Example 3: Python Developer - Advanced
```
[Copy the prompt above and replace:]
- [NUMBER] = 100
- [ROLE_NAME] = Python Developer
- [LEVEL] = Advanced
- [SPECIFIC_TOPICS] = Advanced Python features, Design patterns, Performance optimization, Async programming, Testing frameworks
```

---

## 📊 **Batch Generation Strategy**

### For 500 Questions per Role/Level:

**Option 1: Single Request (if AI supports large output)**
- Request: "Generate 500 questions..."
- Risk: May hit token limits

**Option 2: Batch Requests (Recommended)**
- Request 1: Generate 100 questions (Topics: HTML, CSS, JavaScript basics)
- Request 2: Generate 100 questions (Topics: DOM, Events, AJAX)
- Request 3: Generate 100 questions (Topics: React, State management)
- Request 4: Generate 100 questions (Topics: Node.js, APIs)
- Request 5: Generate 100 questions (Topics: Database, Security, Best practices)

**Option 3: Progressive Generation**
- Start with 50 questions
- Review quality and distribution
- Adjust prompt if needed
- Generate remaining in batches

---

## ✅ **Quality Verification Checklist**

After AI generates questions, verify:

1. **Correct Answer Distribution:**
   ```sql
   SELECT correct_option, COUNT(*) 
   FROM fullstack_mcq_questions 
   WHERE level = 'beginner' 
   GROUP BY correct_option;
   ```
   - Should be roughly: A=125, B=125, C=125, D=125 (for 500 questions)

2. **No Duplicate Questions:**
   ```sql
   SELECT question, COUNT(*) 
   FROM fullstack_mcq_questions 
   GROUP BY question 
   HAVING COUNT(*) > 1;
   ```

3. **All Options Filled:**
   ```sql
   SELECT COUNT(*) 
   FROM fullstack_mcq_questions 
   WHERE option_a IS NULL OR option_b IS NULL 
   OR option_c IS NULL OR option_d IS NULL;
   ```

---

## 🔧 **Post-Generation Script**

After getting JSON from AI, use this PHP script to import:

```php
<?php
// import_questions.php
// Convert AI JSON to SQL INSERT statements

$json = file_get_contents('ai_questions.json');
$questions = json_decode($json, true);

$role = 'Full Stack Developer';
$level = 'beginner';
$table = 'fullstack_mcq_questions';

echo "INSERT INTO `{$table}` (`role`, `level`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES\n";

foreach ($questions as $index => $q) {
    $id = $index + 1;
    $question = addslashes($q['question']);
    $opt_a = addslashes($q['option_a']);
    $opt_b = addslashes($q['option_b']);
    $opt_c = addslashes($q['option_c']);
    $opt_d = addslashes($q['option_d']);
    $correct = $q['correct_option'];
    
    $comma = ($index < count($questions) - 1) ? ',' : ';';
    
    echo "({$id}, '{$role}', '{$level}', '{$question}', '{$opt_a}', '{$opt_b}', '{$opt_c}', '{$opt_d}', '{$correct}'){$comma}\n";
}
?>
```

---

## 💡 **Pro Tips**

1. **Start Small:** Generate 10-20 questions first, verify quality
2. **Iterate:** Refine prompt based on initial results
3. **Mix Sources:** Combine AI-generated + manually curated questions
4. **Review:** Always review AI-generated questions for accuracy
5. **Update:** Keep questions updated with latest technologies

---

## 🚀 **Quick Start**

1. Copy the main prompt above
2. Replace [NUMBER], [ROLE_NAME], [LEVEL], [SPECIFIC_TOPICS]
3. Paste into ChatGPT/Claude/Gemini
4. Get JSON output
5. Use import script to add to database
6. Verify distribution with SQL queries

---

**Note:** Always review AI-generated questions for technical accuracy before using in production assessments.

