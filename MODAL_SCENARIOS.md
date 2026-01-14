# 🔔 Modal Scenarios - When Do Credentials Mismatch Modals Appear?

## 📋 **Overview**

System me **2 types ke modals** hain jo credentials mismatch par show hote hain:

1. **"Credentials Mismatch" Modal** (GET request - Resume scenario)
2. **"In-Progress Quiz Found" Modal** (POST request - New login scenario)

---

## 🎯 **Modal 1: "Credentials Mismatch"**

### **Kab Aata Hai:**
Jab user **quiz page ko resume** karne ki koshish karta hai (page reload, back button, direct URL access)

### **Trigger Conditions:**
1. User ne pehle quiz start kiya tha
2. User ne page reload kiya / back button press kiya / direct URL se access kiya
3. Session me `quiz_attempt_id` aur `quiz_user_id` hai
4. **BUT** session me saved credentials database me saved credentials se **match nahi karte**

### **Exact Scenario:**
```
Step 1: User A ne quiz start kiya
  - Name: "John Doe"
  - Email: "john@example.com"
  - Phone: "9876543210"
  - Role: "Backend Developer"
  - Level: "Beginner"
  - Session me save ho gaya

Step 2: User A ne browser close kar diya / page reload kiya

Step 3: User A ne same browser me wapas aaya
  - Session still active
  - quiz.php GET request trigger hua

Step 4: System check karta hai:
  - Session me: name="John Doe", role="Backend Developer"
  - Database me: name="John Doe", role="Backend Developer"
  - ✅ Match = Quiz resume ho jayega

Step 5: BUT agar session me kuch change ho gaya ho:
  - Session me: name="John Doe", role="Python Developer" (different!)
  - Database me: name="John Doe", role="Backend Developer"
  - ❌ Mismatch = "Credentials Mismatch" modal show hoga
```

### **Code Location:**
- **File:** `quiz.php`
- **Lines:** 87-170
- **Request Type:** GET
- **Check:** Session credentials vs Database credentials

### **When It Shows:**
- ✅ Page reload (F5, Ctrl+R)
- ✅ Back button se wapas aana
- ✅ Direct URL access (`quiz.php`)
- ✅ Browser close ke baad wapas aana (agar session active hai)
- ❌ **NAHI** aayega agar session expire ho gaya ho

---

## 🎯 **Modal 2: "In-Progress Quiz Found"**

### **Kab Aata Hai:**
Jab user **naya login** karta hai (form submit) lekin **in-progress quiz** already exist karta hai with **different credentials**

### **Trigger Conditions:**
1. User ne pehle quiz start kiya tha (in-progress)
2. User ne form submit kiya with **same email/phone** but **different name/role/level**
3. System ne existing user find kiya
4. System ne check kiya ki in-progress attempt hai
5. **BUT** current form data database/attempt data se **match nahi karta**

### **Exact Scenario:**
```
Step 1: User A ne quiz start kiya
  - Name: "John Doe"
  - Email: "john@example.com"
  - Phone: "9876543210"
  - Role: "Backend Developer"
  - Level: "Beginner"
  - Quiz in-progress hai

Step 2: User A ne browser close kar diya / chala gaya

Step 3: User A ne wapas aake form fill kiya
  - Name: "John Doe" ✅ (same)
  - Email: "john@example.com" ✅ (same)
  - Phone: "9876543210" ✅ (same)
  - Role: "Python Developer" ❌ (DIFFERENT!)
  - Level: "Beginner" ✅ (same)

Step 4: System check karta hai:
  - Existing user found (same email/phone)
  - In-progress attempt found
  - Current form: role="Python Developer"
  - Database/Attempt: role="Backend Developer"
  - ❌ Mismatch = "In-Progress Quiz Found" modal show hoga
```

### **Code Location:**
- **File:** `quiz.php`
- **Lines:** 388-470
- **Request Type:** POST
- **Check:** Current form data vs Database/Attempt credentials

### **When It Shows:**
- ✅ Form submit with same email/phone but different name
- ✅ Form submit with same email/phone but different role
- ✅ Form submit with same email/phone but different level
- ✅ Form submit with same email/phone but different mobile (rare case)
- ❌ **NAHI** aayega agar:
  - User already submitted quiz (different modal: "Already Attempted")
  - No in-progress attempt exists
  - All credentials match perfectly

---

## 📊 **Comparison Table**

| Feature | Modal 1: Credentials Mismatch | Modal 2: In-Progress Quiz Found |
|---------|------------------------------|--------------------------------|
| **Request Type** | GET | POST |
| **Trigger** | Resume quiz (page reload) | New login (form submit) |
| **Session** | Active session required | Session may or may not exist |
| **Check Against** | Session vs Database | Form data vs Database/Attempt |
| **When Shows** | Session credentials changed | Form credentials don't match attempt |
| **Code Lines** | 87-170 | 388-470 |

---

## 🔍 **Detailed Scenarios**

### **Scenario A: Normal Resume (No Modal)**
```
1. User starts quiz → Session created
2. User reloads page → GET request
3. Session credentials match database ✅
4. Quiz resumes normally
5. ❌ No modal
```

### **Scenario B: Resume with Changed Session (Modal 1)**
```
1. User starts quiz → Session created
2. User manually changes session (rare) OR session corrupted
3. User reloads page → GET request
4. Session credentials DON'T match database ❌
5. ✅ Modal 1: "Credentials Mismatch" shows
```

### **Scenario C: New Login with Matching Credentials (No Modal)**
```
1. User starts quiz → In-progress attempt exists
2. User closes browser
3. User fills form with SAME credentials
4. Form submit → POST request
5. All credentials match ✅
6. Quiz resumes
7. ❌ No modal
```

### **Scenario D: New Login with Different Role/Level (Modal 2)**
```
1. User starts Backend Beginner quiz
2. User closes browser
3. User fills form with Python Intermediate (same email/phone)
4. Form submit → POST request
5. Credentials DON'T match attempt ❌
6. ✅ Modal 2: "In-Progress Quiz Found" shows
```

### **Scenario E: Already Submitted (Different Modal)**
```
1. User completes quiz → Status = 'submitted'
2. User tries to login again
3. Form submit → POST request
4. System finds submitted quiz
5. ✅ Different modal: "Already Attempted" shows
6. ❌ NOT credentials mismatch modal
```

---

## ⚠️ **Important Notes**

1. **Location Update Allowed:**
   - Location (place) field can be updated without triggering modal
   - Only name, email, mobile, role, level must match

2. **Session vs Database:**
   - Modal 1 checks: Session data vs Database data
   - Modal 2 checks: Form data vs Database/Attempt data

3. **Auto-Fill on OK:**
   - Both modals redirect with URL parameters
   - Form automatically fills with correct credentials
   - User can directly submit without re-typing

4. **Security:**
   - Prevents unauthorized access to other users' quizzes
   - Prevents data overwrite with wrong role/level
   - Ensures quiz integrity

---

## 🎯 **Quick Reference**

**Modal 1 ("Credentials Mismatch") shows when:**
- ✅ GET request (resume)
- ✅ Session active
- ✅ Session credentials ≠ Database credentials

**Modal 2 ("In-Progress Quiz Found") shows when:**
- ✅ POST request (new login)
- ✅ Existing user found
- ✅ In-progress attempt exists
- ✅ Form credentials ≠ Database/Attempt credentials

**No Modal when:**
- ✅ All credentials match perfectly
- ✅ No in-progress attempt exists
- ✅ User already submitted (different modal)

