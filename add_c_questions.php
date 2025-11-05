<?php
// ===============================
// File: add_c_questions.php
// Script to add 3 C Programming questions to the database
// Run this file once by accessing it in your browser: http://localhost/toss-quiz/add_c_questions.php
// ===============================
include 'db.php';

// Array of 3 C Programming questions
$questions = [
    [
        'What will be the output of the following C code?

#include <stdio.h>
int main()
{
    int a = 5;
    printf("%d %d %d\n", a, a++, ++a);
    return 0;
}',
        '5 5 7',
        '7 6 7',
        'Undefined behavior',
        '5 6 7',
        'C'
    ],
    [
        'What will be the output of the following C code?

#include <stdio.h>
int main()
{
    int x = 10;
    int y = (x++, ++x, x++);
    printf("%d\n", y);
    return 0;
}',
        '10',
        '11',
        '12',
        '13',
        'C'
    ],
    [
        'What will be the output of the following C code?

#include <stdio.h>
int main()
{
    char *ptr = "C Programming";
    *ptr = \'c\';
    printf("%s\n", ptr);
    return 0;
}',
        'c Programming',
        'C Programming',
        'Segmentation fault / Runtime error',
        'Undefined output',
        'C'
    ]
];

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
echo "<!DOCTYPE html><html><head><title>C Questions Added</title>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; background: #f0f2f5; }";
echo ".container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }";
echo "h2 { color: #004080; } .success { color: green; } .error { color: red; }</style></head><body>";
echo "<div class='container'>";
echo "<h2>C Programming Questions Addition Status</h2>";

if ($inserted == count($questions)) {
    echo "<p class='success'><strong>✓ Success!</strong> All " . count($questions) . " C Programming questions have been added to the database.</p>";
    echo "<p>The quiz will now randomly select from all available questions (including these 3 C questions).</p>";
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

echo "<p><a href='index.php'>← Go to Quiz Registration</a> | <a href='admin_view.php'>View Admin</a></p>";
echo "</div></body></html>";
?>

