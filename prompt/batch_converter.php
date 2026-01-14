<?php
/**
 * Batch JSON to SQL Converter
 * Converts multiple JSON files (Beginner, Intermediate, Advanced) to SQL
 * 
 * Usage:
 * 1. Generate 3 JSON files: [role]_beginner.json, [role]_intermediate.json, [role]_advanced.json
 * 2. Update configuration below
 * 3. Run: php batch_converter.php
 * 4. Import output SQL files to database
 */

// ============================================
// CONFIGURATION
// ============================================
$config = [
    'role' => 'Full Stack Developer',  // Change as needed
    'json_files' => [
        'beginner' => 'fullstack_beginner.json',
        'intermediate' => 'fullstack_intermediate.json',
        'advanced' => 'fullstack_advanced.json',
    ],
    'output_files' => [
        'beginner' => 'fullstack_beginner.sql',
        'intermediate' => 'fullstack_intermediate.sql',
        'advanced' => 'fullstack_advanced.sql',
    ],
    'start_ids' => [
        'beginner' => 1,
        'intermediate' => 168,      // 167 + 1
        'advanced' => 335,          // 167 + 167 + 1
    ],
];

// Role to table mapping
$roleToTable = [
    'Backend Developer' => 'backend_mcq_questions',
    'Python Developer' => 'python_mcq_questions',
    'Flutter Developer' => 'flutter_mcq_questions',
    'Mern Developer' => 'mern_mcq_questions',
    'Full Stack Developer' => 'fullstack_mcq_questions',
];

// Auto-detect table from role
$table = $roleToTable[$config['role']] ?? 'fullstack_mcq_questions';

echo "========================================\n";
echo "Batch JSON to SQL Converter\n";
echo "========================================\n";
echo "Role: {$config['role']}\n";
echo "Table: {$table}\n";
echo "Target: 500 questions total\n";
echo "  - Beginner: 167\n";
echo "  - Intermediate: 167\n";
echo "  - Advanced: 166\n";
echo "========================================\n\n";

$totalStats = [
    'beginner' => ['count' => 0, 'distribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0]],
    'intermediate' => ['count' => 0, 'distribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0]],
    'advanced' => ['count' => 0, 'distribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0]],
];

// Process each level
foreach (['beginner', 'intermediate', 'advanced'] as $level) {
    $jsonFile = $config['json_files'][$level];
    $outputFile = $config['output_files'][$level];
    $startId = $config['start_ids'][$level];
    
    echo "\n--- Processing {$level} level ---\n";
    
    if (!file_exists($jsonFile)) {
        echo "⚠️  WARNING: {$jsonFile} not found, skipping...\n";
        continue;
    }
    
    $json = file_get_contents($jsonFile);
    $questions = json_decode($json, true);
    
    if (!$questions || !is_array($questions)) {
        echo "⚠️  ERROR: Invalid JSON in {$jsonFile}\n";
        continue;
    }
    
    $count = count($questions);
    $totalStats[$level]['count'] = $count;
    
    echo "Found {$count} questions\n";
    
    // Analyze distribution
    $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
    foreach ($questions as $q) {
        if (isset($q['correct_option'])) {
            $dist = strtoupper(trim($q['correct_option']));
            if (isset($distribution[$dist])) {
                $distribution[$dist]++;
            }
        }
    }
    $totalStats[$level]['distribution'] = $distribution;
    
    echo "Distribution: A={$distribution['A']}, B={$distribution['B']}, C={$distribution['C']}, D={$distribution['D']}\n";
    
    // Generate SQL
    $sql = "-- Generated SQL for {$config['role']} - {$level}\n";
    $sql .= "-- Total questions: {$count}\n";
    $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "INSERT INTO `{$table}` (`id`, `role`, `level`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES\n";
    
    $id = $startId;
    $validCount = 0;
    
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
        
        $sql .= "({$id}, '{$config['role']}', '{$level}', '{$question}', '{$opt_a}', '{$opt_b}', '{$opt_c}', '{$opt_d}', '{$correct}'){$comma}\n";
        
        $id++;
        $validCount++;
    }
    
    // Save to file
    file_put_contents($outputFile, $sql);
    
    echo "✓ Generated {$validCount} valid questions\n";
    echo "✓ Saved to: {$outputFile}\n";
}

// Final Summary
echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Role: {$config['role']}\n";
echo "Table: {$table}\n\n";

$grandTotal = 0;
foreach (['beginner', 'intermediate', 'advanced'] as $level) {
    $count = $totalStats[$level]['count'];
    $dist = $totalStats[$level]['distribution'];
    $grandTotal += $count;
    
    echo "{$level}: {$count} questions\n";
    echo "  Distribution: A={$dist['A']}, B={$dist['B']}, C={$dist['C']}, D={$dist['D']}\n";
    
    // Check if balanced
    $expected = $count / 4;
    $isBalanced = true;
    foreach ($dist as $letter => $cnt) {
        if (abs($cnt - $expected) > $expected * 0.2) { // 20% tolerance
            $isBalanced = false;
        }
    }
    echo "  " . ($isBalanced ? "✓ Balanced" : "⚠️  Not balanced") . "\n\n";
}

echo "GRAND TOTAL: {$grandTotal} questions\n";
echo "Target: 500 questions\n";
echo ($grandTotal == 500 ? "✓ Perfect!" : "⚠️  " . abs(500 - $grandTotal) . " questions difference") . "\n";

echo "\n========================================\n";
echo "Next Steps:\n";
echo "1. Review generated SQL files\n";
echo "2. Import to database using phpMyAdmin\n";
echo "3. Verify with SQL:\n";
echo "   SELECT level, correct_option, COUNT(*) \n";
echo "   FROM {$table} \n";
echo "   WHERE role = '{$config['role']}' \n";
echo "   GROUP BY level, correct_option;\n";
echo "========================================\n";

