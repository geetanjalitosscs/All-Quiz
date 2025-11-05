-- SQL script to add 3 C Programming questions to the database
-- Run this in phpMyAdmin or your MySQL client
-- These questions will be included in the quiz along with existing questions

-- Insert 3 C Programming questions
INSERT INTO `questions` (`question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
('What will be the output of the following C code?\n\n#include <stdio.h>\nint main()\n{\n    int a = 5;\n    printf(\"%d %d %d\\n\", a, a++, ++a);\n    return 0;\n}', '5 5 7', '7 6 7', 'Undefined behavior', '5 6 7', 'C'),
('What will be the output of the following C code?\n\n#include <stdio.h>\nint main()\n{\n    int x = 10;\n    int y = (x++, ++x, x++);\n    printf(\"%d\\n\", y);\n    return 0;\n}', '10', '11', '12', '13', 'C'),
('What will be the output of the following C code?\n\n#include <stdio.h>\nint main()\n{\n    char *ptr = \"C Programming\";\n    *ptr = \'c\';\n    printf(\"%s\\n\", ptr);\n    return 0;\n}', 'c Programming', 'C Programming', 'Segmentation fault / Runtime error', 'Undefined output', 'C');

