-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 11:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `back-end_developer_quiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(102, 'Which of the following best describes a REST API?', 'A stateful API protocol', 'A stateless architectural style for communication', 'A protocol used for sending emails', 'A database query language', 'B'),
(103, 'What does HTTP status code 404 mean?', 'Bad Request', 'Forbidden', 'Not Found', 'Internal Server Error', 'C'),
(104, 'Which data structure provides average O(1) lookup time?', 'Array', 'Hash Map', 'Linked List', 'Binary Tree', 'B'),
(106, 'What is the primary purpose of indexing in a database?', 'Store large data', 'Improve query performance', 'Ensure data security', 'Format tables', 'B'),
(107, 'Which SQL keyword is used to remove duplicate rows in a result?', 'DISTINCT', 'UNIQUE', 'FILTER', 'ONLY', 'A'),
(108, 'What does ACID stand for in databases?', 'Atomicity, Consistency, Isolation, Durability', 'Access, Control, Information, Data', 'Array, Condition, Integer, Double', 'None of the above', 'A'),
(109, 'Which of the following is a NoSQL database?', 'MySQL', 'PostgreSQL', 'MongoDB', 'Oracle', 'C'),
(110, 'Which authentication mechanism uses tokens for secure access?', 'Basic Auth', 'JWT', 'FTP', 'SSH', 'B'),
(111, 'Which is true about microservices architecture?', 'It is a single large codebase', 'It divides the application into small independent services', 'It cannot scale', 'It runs only on cloud', 'B'),
(112, 'Which command is used to build a Docker image?', 'docker run', 'docker build', 'docker start', 'docker push', 'B'),
(113, 'Which HTTP method is idempotent?', 'POST', 'PATCH', 'PUT', 'CONNECT', 'C'),
(114, 'What does SQL JOIN do?', 'Deletes data', 'Combines rows from two tables', 'Creates a new database', 'Encrypts data', 'B'),
(115, 'Which of the following helps prevent SQL injection?', 'Inline queries', 'Prepared statements', 'Storing credentials in code', 'Remove indexes', 'B'),
(116, 'In caching, what does TTL stand for?', 'Time to Load', 'Time to Live', 'Time to Limit', 'Temporary Transfer Level', 'B'),
(117, 'Which of the following is a message queue service?', 'MySQL', 'RabbitMQ', 'Redis only', 'CSV', 'B'),
(118, 'Which of the following is used for API documentation?', 'Swagger', 'Excel', 'Notepad', 'Terminal', 'A'),
(119, 'Which port does HTTP use by default?', '21', '22', '80', '443', 'C'),
(120, 'Which of the following is a key benefit of horizontal scaling?', 'Adding more CPU to one machine', 'Adding more machines to handle load', 'Reducing memory usage', 'None', 'B'),
(121, 'Which is a distributed version control system?', 'SVN', 'Git', 'FTP', 'SFTP', 'B'),
(122, 'What does ORM stand for?', 'Object Relational Mapping', 'Object Return Method', 'Online Resource Manager', 'Order Resource Model', 'A'),
(123, 'Which is used to secure HTTPS communication?', 'SSL/TLS', 'FTP', 'Telnet', 'DNS', 'A'),
(124, 'What is the default port for HTTPS?', '80', '443', '25', '110', 'B'),
(125, 'Which of the following is used to handle cross-origin requests?', 'JWT', 'CORS', 'FTP', 'DNS', 'B'),
(126, 'Which database operation does DELETE perform?', 'Remove table', 'Remove records', 'Remove database', 'None', 'B'),
(127, 'Which of the following is a relationship type in databases?', 'Binary', 'One-to-many', 'Integer', 'Joiner', 'B'),
(128, 'What does MVC stand for?', 'Model View Controller', 'Main View Center', 'Model Value Control', 'Modular Version Control', 'A'),
(129, 'In JWT, the payload is:', 'Encrypted always', 'Base64 encoded', 'Stored in Redis', 'Always private', 'B'),
(130, 'Which language is commonly used for backend?', 'HTML', 'CSS', 'Node.js', 'Bootstrap', 'C'),
(131, 'Which protocol is used to transfer web pages?', 'HTTP', 'FTP', 'SMTP', 'UDP', 'A'),
(132, 'Which of the following improves API speed?', 'Caching', 'Removing logs', 'Using print statements', 'Increasing timeout', 'A'),
(133, 'Which is a cloud service provider?', 'VS Code', 'AWS', 'Photoshop', 'VLC', 'B'),
(134, 'What does CI/CD stand for?', 'Code Integration / Code Development', 'Continuous Integration / Continuous Deployment', 'Continuous Input / Continuous Data', 'None', 'B'),
(135, 'Which database command sorts results?', 'ORDER BY', 'GROUP BY', 'SORT', 'FILTER', 'A'),
(136, 'Which helps prevent XSS attacks?', 'Input validation and output encoding', 'Inline scripts', 'Exposing tokens', 'Root login', 'A'),
(137, 'What is the main purpose of API Gateway in microservices?', 'Storing data', 'Routing requests to services', 'Building UI', 'Monitoring logs', 'B'),
(138, 'Which of the following represents container orchestration?', 'Docker', 'Kubernetes', 'GitHub', 'Python', 'B'),
(139, 'Which type of database stores data in tables?', 'SQL', 'NoSQL', 'Redis', 'CSV', 'A'),
(141, 'Which HTTP method is commonly used to retrieve data?', 'GET', 'POST', 'PUT', 'DELETE', 'A'),
(142, 'What is the purpose of a primary key?', 'Sort records', 'Uniquely identify a record', 'Format data', 'Filter records', 'B'),
(143, 'Which type of scaling adds more resources to a single machine?', 'Horizontal scaling', 'Vertical scaling', 'Cloud scaling', 'Parallel scaling', 'B'),
(144, 'Which is an example of server-side language?', 'HTML', 'CSS', 'PHP', 'React', 'C'),
(145, 'What is WebSocket used for?', 'One-way communication', 'Real-time two-way communication', 'File transfer', 'Email sending', 'B'),
(146, 'Which tool is commonly used for load testing?', 'JMeter', 'Excel', 'Chrome', 'VS Code', 'A'),
(147, 'What is the main purpose of a reverse proxy?', 'Connect database', 'Forward client requests to servers', 'Delete logs', 'Compile code', 'B'),
(148, 'Which Redis data type is used to store key-value pairs?', 'List', 'Hash', 'Set', 'Sorted Set', 'B'),
(149, 'What is latency?', 'Amount of data transferred', 'Delay in response time', 'CPU usage', 'Number of users', 'B'),
(150, 'Which command shows running Docker containers?', 'docker ps', 'docker pull', 'docker rm', 'docker stop', 'A'),
(151, 'Which of these is used for API load balancing?', 'Gmail', 'NGINX', 'Excel', 'Redis', 'B'),
(152, 'What will be the output of the following C code?\n\n#include <stdio.h>\nint main()\n{\n    int a = 5;\n    printf(\"%d %d %d\\n\", a, a++, ++a);\n    return 0;\n}', '5 5 7', '7 6 7', 'Undefined behavior', '5 6 7', 'C'),
(153, 'What will be the output of the following C code?\n\n#include <stdio.h>\nint main()\n{\n    int x = 10;\n    int y = (x++, ++x, x++);\n    printf(\"%d\\n\", y);\n    return 0;\n}', '10', '11', '12', '13', 'C'),
(154, 'What will be the output of the following C code?\n\n#include <stdio.h>\nint main()\n{\n    char *ptr = \"C Programming\";\n    *ptr = \'c\';\n    printf(\"%s\\n\", ptr);\n    return 0;\n}', 'c Programming', 'C Programming', 'Segmentation fault / Runtime error', 'Undefined output', 'C');

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `selected_option` enum('A','B','C','D') DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `position`, `place`, `mobile`, `email`, `submitted_at`) VALUES
(13, 'Amandeep kaur', 'Android Developer', 'Delhi', NULL, NULL, '2025-06-10 09:30:23'),
(14, 'Abhishek Kumar Pandey', 'Flutter Developer', 'Lucknow', NULL, NULL, '2025-06-11 07:47:57'),
(15, 'ishwar', 'flutter developer', 'jaipur', NULL, NULL, '2025-06-11 11:40:38'),
(16, 'sonu kumar jha', 'Flutter Developer', 'Noida', NULL, NULL, '2025-06-26 11:39:04'),
(17, 'Devesh Kumar Patel', 'flutter developer', 'noida', NULL, NULL, '2025-07-01 10:35:01'),
(18, 'Nishant Kumar', 'Flutter Developer', 'Gurugao', NULL, NULL, '2025-07-04 07:44:08'),
(19, ' Hanuman Sahani', 'Flutter Developer', 'Delhi', NULL, NULL, '2025-07-07 08:40:04'),
(20, ' Hanuman Sahani', 'Flutter Developer', 'Delhi', NULL, NULL, '2025-07-07 08:40:05'),
(21, 'rupesh sahu', 'MERN', 'Jabalpur', NULL, NULL, '2025-11-05 08:32:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=405;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
