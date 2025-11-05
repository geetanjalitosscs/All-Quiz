<?php
// ===============================
// File: replace_questions.php
// Script to replace existing questions with Backend Developer MCQ questions
// Run this file once by accessing it in your browser: http://localhost/toss-quiz/replace_questions.php
// ===============================
include 'db.php';

// Array of questions with their options and correct answers
$questions = [
    ['Which of the following best describes a REST API?', 'A stateful API protocol', 'A stateless architectural style for communication', 'A protocol used for sending emails', 'A database query language', 'B'],
    ['What does HTTP status code 404 mean?', 'Bad Request', 'Forbidden', 'Not Found', 'Internal Server Error', 'C'],
    ['Which data structure provides average O(1) lookup time?', 'Array', 'Hash Map', 'Linked List', 'Binary Tree', 'B'],
    ['Which of the following is NOT an HTTP method?', 'GET', 'POST', 'SEND', 'DELETE', 'C'],
    ['What is the primary purpose of indexing in a database?', 'Store large data', 'Improve query performance', 'Ensure data security', 'Format tables', 'B'],
    ['Which SQL keyword is used to remove duplicate rows in a result?', 'DISTINCT', 'UNIQUE', 'FILTER', 'ONLY', 'A'],
    ['What does ACID stand for in databases?', 'Atomicity, Consistency, Isolation, Durability', 'Access, Control, Information, Data', 'Array, Condition, Integer, Double', 'None of the above', 'A'],
    ['Which of the following is a NoSQL database?', 'MySQL', 'PostgreSQL', 'MongoDB', 'Oracle', 'C'],
    ['Which authentication mechanism uses tokens for secure access?', 'Basic Auth', 'JWT', 'FTP', 'SSH', 'B'],
    ['Which is true about microservices architecture?', 'It is a single large codebase', 'It divides the application into small independent services', 'It cannot scale', 'It runs only on cloud', 'B'],
    ['Which command is used to build a Docker image?', 'docker run', 'docker build', 'docker start', 'docker push', 'B'],
    ['Which HTTP method is idempotent?', 'POST', 'PATCH', 'PUT', 'CONNECT', 'C'],
    ['What does SQL JOIN do?', 'Deletes data', 'Combines rows from two tables', 'Creates a new database', 'Encrypts data', 'B'],
    ['Which of the following helps prevent SQL injection?', 'Inline queries', 'Prepared statements', 'Storing credentials in code', 'Remove indexes', 'B'],
    ['In caching, what does TTL stand for?', 'Time to Load', 'Time to Live', 'Time to Limit', 'Temporary Transfer Level', 'B'],
    ['Which of the following is a message queue service?', 'MySQL', 'RabbitMQ', 'Redis only', 'CSV', 'B'],
    ['Which of the following is used for API documentation?', 'Swagger', 'Excel', 'Notepad', 'Terminal', 'A'],
    ['Which port does HTTP use by default?', '21', '22', '80', '443', 'C'],
    ['Which of the following is a key benefit of horizontal scaling?', 'Adding more CPU to one machine', 'Adding more machines to handle load', 'Reducing memory usage', 'None', 'B'],
    ['Which is a distributed version control system?', 'SVN', 'Git', 'FTP', 'SFTP', 'B'],
    ['What does ORM stand for?', 'Object Relational Mapping', 'Object Return Method', 'Online Resource Manager', 'Order Resource Model', 'A'],
    ['Which is used to secure HTTPS communication?', 'SSL/TLS', 'FTP', 'Telnet', 'DNS', 'A'],
    ['What is the default port for HTTPS?', '80', '443', '25', '110', 'B'],
    ['Which of the following is used to handle cross-origin requests?', 'JWT', 'CORS', 'FTP', 'DNS', 'B'],
    ['Which database operation does DELETE perform?', 'Remove table', 'Remove records', 'Remove database', 'None', 'B'],
    ['Which of the following is a relationship type in databases?', 'Binary', 'One-to-many', 'Integer', 'Joiner', 'B'],
    ['What does MVC stand for?', 'Model View Controller', 'Main View Center', 'Model Value Control', 'Modular Version Control', 'A'],
    ['In JWT, the payload is:', 'Encrypted always', 'Base64 encoded', 'Stored in Redis', 'Always private', 'B'],
    ['Which language is commonly used for backend?', 'HTML', 'CSS', 'Node.js', 'Bootstrap', 'C'],
    ['Which protocol is used to transfer web pages?', 'HTTP', 'FTP', 'SMTP', 'UDP', 'A'],
    ['Which of the following improves API speed?', 'Caching', 'Removing logs', 'Using print statements', 'Increasing timeout', 'A'],
    ['Which is a cloud service provider?', 'VS Code', 'AWS', 'Photoshop', 'VLC', 'B'],
    ['What does CI/CD stand for?', 'Code Integration / Code Development', 'Continuous Integration / Continuous Deployment', 'Continuous Input / Continuous Data', 'None', 'B'],
    ['Which database command sorts results?', 'ORDER BY', 'GROUP BY', 'SORT', 'FILTER', 'A'],
    ['Which helps prevent XSS attacks?', 'Input validation and output encoding', 'Inline scripts', 'Exposing tokens', 'Root login', 'A'],
    ['What is the main purpose of API Gateway in microservices?', 'Storing data', 'Routing requests to services', 'Building UI', 'Monitoring logs', 'B'],
    ['Which of the following represents container orchestration?', 'Docker', 'Kubernetes', 'GitHub', 'Python', 'B'],
    ['Which type of database stores data in tables?', 'SQL', 'NoSQL', 'Redis', 'CSV', 'A'],
    ['Which language runs in the browser primarily?', 'Python', 'JavaScript', 'PHP', 'SQL', 'B'],
    ['Which HTTP method is commonly used to retrieve data?', 'GET', 'POST', 'PUT', 'DELETE', 'A'],
    ['What is the purpose of a primary key?', 'Sort records', 'Uniquely identify a record', 'Format data', 'Filter records', 'B'],
    ['Which type of scaling adds more resources to a single machine?', 'Horizontal scaling', 'Vertical scaling', 'Cloud scaling', 'Parallel scaling', 'B'],
    ['Which is an example of server-side language?', 'HTML', 'CSS', 'PHP', 'React', 'C'],
    ['What is WebSocket used for?', 'One-way communication', 'Real-time two-way communication', 'File transfer', 'Email sending', 'B'],
    ['Which tool is commonly used for load testing?', 'JMeter', 'Excel', 'Chrome', 'VS Code', 'A'],
    ['What is the main purpose of a reverse proxy?', 'Connect database', 'Forward client requests to servers', 'Delete logs', 'Compile code', 'B'],
    ['Which Redis data type is used to store key-value pairs?', 'List', 'Hash', 'Set', 'Sorted Set', 'B'],
    ['What is latency?', 'Amount of data transferred', 'Delay in response time', 'CPU usage', 'Number of users', 'B'],
    ['Which command shows running Docker containers?', 'docker ps', 'docker pull', 'docker rm', 'docker stop', 'A'],
    ['Which of these is used for API load balancing?', 'Gmail', 'NGINX', 'Excel', 'Redis', 'B']
];

// Delete all existing questions
$conn->query("DELETE FROM questions");

// Prepare statement for inserting questions
$stmt = $conn->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?)");

$inserted = 0;
$errors = [];

foreach ($questions as $index => $q) {
    $stmt->bind_param("ssssss", $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
    
    if ($stmt->execute()) {
        $inserted++;
    } else {
        $errors[] = "Error inserting question " . ($index + 1) . ": " . $stmt->error;
    }
}

$stmt->close();

// Display results
echo "<!DOCTYPE html><html><head><title>Questions Replaced</title>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; background: #f0f2f5; }";
echo ".container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }";
echo "h2 { color: #004080; } .success { color: green; } .error { color: red; }</style></head><body>";
echo "<div class='container'>";
echo "<h2>Questions Replacement Status</h2>";

if ($inserted == count($questions)) {
    echo "<p class='success'><strong>✓ Success!</strong> All " . count($questions) . " questions have been replaced.</p>";
} else {
    echo "<p class='error'><strong>⚠ Warning:</strong> Only $inserted out of " . count($questions) . " questions were inserted.</p>";
}

if (!empty($errors)) {
    echo "<h3>Errors:</h3><ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>$error</li>";
    }
    echo "</ul>";
}

echo "<p><a href='index.php'>← Go to Quiz Registration</a></p>";
echo "</div></body></html>";
?>

