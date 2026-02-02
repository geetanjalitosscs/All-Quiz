<?php
/**
 * JSON to SQL Converter
 * Converts AI-generated JSON questions to SQL INSERT statements
 * 
 * Usage:
 * 1. Get JSON from AI (ChatGPT/Claude)
 * 2. Save as questions.json
 * 3. Run: php json_to_sql_converter.php
 * 4. Copy output SQL and import to database
 */

// Configuration
$config = [
    'role' => 'Full Stack Developer',  // Change as needed
    'level' => 'beginner',              // beginner, intermediate, advanced
    'table' => 'fullstack_mcq_questions', // Change table name as needed
    'json_file' => 'questions.json',     // Input JSON file
    'output_file' => 'output.sql',      // Output SQL file
    'start_id' => 1,                    // Starting ID (adjust if appending)
];

// Role to table mapping
$roleToTable = [
    'Backend Developer' => 'backend_mcq_questions',
    'Python Developer' => 'python_mcq_questions',
    'Flutter Developer' => 'flutter_mcq_questions',
    'Mern Developer' => 'mern_mcq_questions',
    'Full Stack Developer' => 'fullstack_mcq_questions',
    'Data Analytics' => 'data_analytics_mcq',
];

// Auto-detect table from role
if (isset($roleToTable[$config['role']])) {
    $config['table'] = $roleToTable[$config['role']];
}

// Read JSON file
if (!file_exists($config['json_file'])) {
    die("Error: {$config['json_file']} not found!\n");
}

$json = file_get_contents($config['json_file']);
$questions = json_decode($json, true);

if (!$questions || !is_array($questions)) {
    die("Error: Invalid JSON format!\n");
}

echo "Found " . count($questions) . " questions\n";
echo "Role: {$config['role']}\n";
echo "Level: {$config['level']}\n";
echo "Table: {$config['table']}\n\n";

// Analyze correct answer distribution
$distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
foreach ($questions as $q) {
    if (isset($q['correct_option'])) {
        $dist = strtoupper($q['correct_option']);
        if (isset($distribution[$dist])) {
            $distribution[$dist]++;
        }
    }
}

echo "Correct Answer Distribution:\n";
echo "  A: {$distribution['A']} (" . round($distribution['A'] / count($questions) * 100, 1) . "%)\n";
echo "  B: {$distribution['B']} (" . round($distribution['B'] / count($questions) * 100, 1) . "%)\n";
echo "  C: {$distribution['C']} (" . round($distribution['C'] / count($questions) * 100, 1) . "%)\n";
echo "  D: {$distribution['D']} (" . round($distribution['D'] / count($questions) * 100, 1) . "%)\n\n";

// Check if distribution is balanced
$expected = count($questions) / 4;
$threshold = $expected * 0.15; // 15% tolerance
$isBalanced = true;
foreach ($distribution as $letter => $count) {
    if (abs($count - $expected) > $threshold) {
        $isBalanced = false;
        echo "⚠️  WARNING: Distribution is NOT balanced! {$letter} has {$count} questions (expected ~{$expected})\n";
    }
}

if ($isBalanced) {
    echo "✓ Distribution looks good!\n\n";
}

// Generate SQL
$sql = "-- Generated SQL for {$config['role']} - {$config['level']}\n";
$sql .= "-- Total questions: " . count($questions) . "\n";
$sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "INSERT INTO `{$config['table']}` (`id`, `role`, `level`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES\n";

$id = $config['start_id'];
foreach ($questions as $index => $q) {
    // Validate required fields
    if (empty($q['question']) || empty($q['option_a']) || empty($q['option_b']) || 
        empty($q['option_c']) || empty($q['option_d']) || empty($q['correct_option'])) {
        echo "⚠️  WARNING: Question " . ($index + 1) . " has missing fields, skipping...\n";
        continue;
    }
    
    // Escape single quotes and backslashes
    $question = addslashes($q['question']);
    $opt_a = addslashes($q['option_a']);
    $opt_b = addslashes($q['option_b']);
    $opt_c = addslashes($q['option_c']);
    $opt_d = addslashes($q['option_d']);
    $correct = strtoupper(trim($q['correct_option']));
    
    // Validate correct_option
    if (!in_array($correct, ['A', 'B', 'C', 'D'])) {
        echo "⚠️  WARNING: Question " . ($index + 1) . " has invalid correct_option: {$correct}, defaulting to A\n";
        $correct = 'A';
    }
    
    $comma = ($index < count($questions) - 1) ? ',' : ';';
    
    $sql .= "({$id}, '{$config['role']}', '{$config['level']}', '{$question}', '{$opt_a}', '{$opt_b}', '{$opt_c}', '{$opt_d}', '{$correct}'){$comma}\n";
    
    $id++;
}

// Save to file
file_put_contents($config['output_file'], $sql);

echo "\n✓ SQL generated successfully!\n";
echo "Output file: {$config['output_file']}\n";
echo "\nNext steps:\n";
echo "1. Review {$config['output_file']}\n";
echo "2. Import to database using phpMyAdmin or command line\n";
echo "3. Verify with: SELECT correct_option, COUNT(*) FROM {$config['table']} WHERE level='{$config['level']}' GROUP BY correct_option;\n";

