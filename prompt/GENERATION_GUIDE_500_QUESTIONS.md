# 📚 Complete Guide: Generating 500 Questions Per Role

## 🎯 **Target Structure**

**Per Role:** 500 total questions
- **Beginner:** 167 questions
- **Intermediate:** 167 questions
- **Advanced:** 166 questions

**Total for 5 Roles:** 2,500 questions
- Full Stack Developer: 500
- Backend Developer: 500
- Python Developer: 500
- Flutter Developer: 500
- MERN Developer: 500

---

## 📋 **Step-by-Step Process**

### **Phase 1: Generate Questions for Each Level**

For each role, you need to generate questions in 3 batches:

#### **Batch 1: Beginner (167 questions)**
1. Open `PROMPT_BEGINNER.txt`
2. Replace `[ROLE_NAME]` with your role
3. Replace `[SPECIFIC_TOPICS]` with beginner topics
4. Copy to ChatGPT/Claude
5. Save output as `[ROLE]_beginner.json`

#### **Batch 2: Intermediate (167 questions)**
1. Open `PROMPT_INTERMEDIATE.txt`
2. Replace `[ROLE_NAME]` with your role
3. Replace `[SPECIFIC_TOPICS]` with intermediate topics
4. Copy to ChatGPT/Claude
5. Save output as `[ROLE]_intermediate.json`

#### **Batch 3: Advanced (166 questions)**
1. Open `PROMPT_ADVANCED.txt`
2. Replace `[ROLE_NAME]` with your role
3. Replace `[SPECIFIC_TOPICS]` with advanced topics
4. Copy to ChatGPT/Claude
5. Save output as `[ROLE]_advanced.json`

---

## 🔧 **Phase 2: Convert to SQL**

### **Option A: Individual Conversion (Recommended)**

For each JSON file:

1. **Update `json_to_sql_converter.php`:**
   ```php
   $config = [
       'role' => 'Full Stack Developer',
       'level' => 'beginner',  // or 'intermediate' or 'advanced'
       'json_file' => 'fullstack_beginner.json',
       'output_file' => 'fullstack_beginner.sql',
       'start_id' => 1,  // Adjust for each batch
   ];
   ```

2. **Run converter:**
   ```bash
   php json_to_sql_converter.php
   ```

3. **Repeat for all 3 levels**

### **Option B: Batch Conversion Script**

Use the provided batch converter (see below).

---

## 📊 **Topic Suggestions by Role & Level**

### **Full Stack Developer**

**Beginner (167 questions):**
- HTML basics, CSS fundamentals, JavaScript basics
- DOM manipulation, Events, AJAX basics
- HTTP methods, REST basics, SQL basics
- Git fundamentals, Responsive design

**Intermediate (167 questions):**
- React/Vue basics, State management
- Node.js, Express.js, REST APIs
- Database design, Authentication
- Error handling, Testing basics

**Advanced (166 questions):**
- Advanced React patterns, Performance optimization
- Microservices, System design
- Security best practices, Scalability
- CI/CD, DevOps basics

### **Backend Developer**

**Beginner (167 questions):**
- Programming fundamentals, Data structures basics
- HTTP, REST basics, Database basics
- API concepts, Error handling basics

**Intermediate (167 questions):**
- Server frameworks (Express, Django, Flask)
- Database design, Authentication & Authorization
- Caching, Message queues
- Testing, API design

**Advanced (166 questions):**
- System architecture, Microservices
- Performance optimization, Scalability
- Security advanced, Distributed systems
- DevOps, Monitoring

### **Python Developer**

**Beginner (167 questions):**
- Python syntax, Data types, Control flow
- Functions, Modules, File I/O
- Basic OOP, Exception handling

**Intermediate (167 questions):**
- Advanced OOP, Decorators, Generators
- Popular libraries (Pandas, NumPy, Requests)
- Web frameworks (Django, Flask)
- Testing, Database integration

**Advanced (166 questions):**
- Advanced Python features, Metaclasses
- Performance optimization, Async programming
- Design patterns, System design
- Machine Learning basics (if applicable)

### **Flutter Developer**

**Beginner (167 questions):**
- Dart basics, Flutter widgets
- State management basics, Navigation
- UI/UX basics, Forms

**Intermediate (167 questions):**
- Advanced state management (Provider, Bloc)
- API integration, Local storage
- Animations, Custom widgets
- Testing, Performance

**Advanced (166 questions):**
- Advanced architecture patterns
- Performance optimization, Native integration
- Complex animations, Custom renderers
- Platform-specific code

### **MERN Developer**

**Beginner (167 questions):**
- MongoDB basics, Express.js basics
- React basics, Node.js basics
- REST API basics, CRUD operations

**Intermediate (167 questions):**
- Advanced MongoDB queries, Aggregation
- Express middleware, Authentication
- React hooks, State management
- API design, Error handling

**Advanced (166 questions):**
- Database optimization, Indexing
- Advanced React patterns, Performance
- System architecture, Scalability
- Security, Best practices

---

## ✅ **Verification Checklist**

After generating all questions, verify:

### **1. Count Verification:**
```sql
-- Check total questions per role
SELECT role, level, COUNT(*) as count
FROM fullstack_mcq_questions
GROUP BY role, level;

-- Should show:
-- Beginner: 167
-- Intermediate: 167
-- Advanced: 166
-- Total: 500
```

### **2. Distribution Verification:**
```sql
-- Check correct answer distribution per level
SELECT level, correct_option, COUNT(*) as count
FROM fullstack_mcq_questions
WHERE role = 'Full Stack Developer'
GROUP BY level, correct_option
ORDER BY level, correct_option;

-- Each level should have roughly:
-- A: ~42, B: ~42, C: ~42, D: ~41 (for Beginner/Intermediate)
-- A: ~42, B: ~42, C: ~41, D: ~41 (for Advanced)
```

### **3. Quality Checks:**
```sql
-- Check for NULL values
SELECT COUNT(*) 
FROM fullstack_mcq_questions 
WHERE question IS NULL 
   OR option_a IS NULL 
   OR option_b IS NULL 
   OR option_c IS NULL 
   OR option_d IS NULL
   OR correct_option IS NULL;

-- Check for duplicates
SELECT question, COUNT(*) 
FROM fullstack_mcq_questions 
GROUP BY question 
HAVING COUNT(*) > 1;
```

---

## 🚀 **Quick Start Example**

### **For Full Stack Developer:**

1. **Generate Beginner:**
   - Use `PROMPT_BEGINNER.txt`
   - Replace: `[ROLE_NAME]` = "Full Stack Developer"
   - Replace: `[SPECIFIC_TOPICS]` = "HTML, CSS, JavaScript, DOM, HTTP, SQL basics"
   - Get 167 questions → Save as `fullstack_beginner.json`

2. **Generate Intermediate:**
   - Use `PROMPT_INTERMEDIATE.txt`
   - Replace: `[ROLE_NAME]` = "Full Stack Developer"
   - Replace: `[SPECIFIC_TOPICS]` = "React, Node.js, Express, REST APIs, Authentication"
   - Get 167 questions → Save as `fullstack_intermediate.json`

3. **Generate Advanced:**
   - Use `PROMPT_ADVANCED.txt`
   - Replace: `[ROLE_NAME]` = "Full Stack Developer"
   - Replace: `[SPECIFIC_TOPICS]` = "Performance optimization, System design, Security, Scalability"
   - Get 166 questions → Save as `fullstack_advanced.json`

4. **Convert to SQL:**
   ```bash
   # Beginner
   php json_to_sql_converter.php  # (update config first)
   
   # Intermediate
   php json_to_sql_converter.php  # (update config)
   
   # Advanced
   php json_to_sql_converter.php  # (update config)
   ```

5. **Import to Database:**
   - Import all 3 SQL files to database
   - Verify with SQL queries above

---

## 💡 **Pro Tips**

1. **Start with one role:** Complete Full Stack Developer first, then replicate for others
2. **Review quality:** Check first 10-20 questions before generating full batch
3. **Adjust prompts:** If AI quality is low, refine prompts based on initial results
4. **Batch processing:** Generate in smaller batches (50-100) if AI has token limits
5. **Backup:** Always backup database before importing new questions

---

## 📝 **File Structure**

```
All-Assessment-Quiz/
├── PROMPT_BEGINNER.txt          # Prompt for 167 beginner questions
├── PROMPT_INTERMEDIATE.txt       # Prompt for 167 intermediate questions
├── PROMPT_ADVANCED.txt           # Prompt for 166 advanced questions
├── json_to_sql_converter.php    # Convert JSON to SQL
├── GENERATION_GUIDE_500_QUESTIONS.md  # This file
│
├── Generated Files (examples):
│   ├── fullstack_beginner.json
│   ├── fullstack_intermediate.json
│   ├── fullstack_advanced.json
│   ├── fullstack_beginner.sql
│   ├── fullstack_intermediate.sql
│   └── fullstack_advanced.sql
```

---

**Total Time Estimate:**
- Per role: ~2-3 hours (generation + conversion + verification)
- All 5 roles: ~10-15 hours

**Good luck! 🚀**

