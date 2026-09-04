-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: food_rescue
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

USE `food_rescue`;

--
-- Dumping data for table `beneficiary_profiles`
--

LOCK TABLES `beneficiary_profiles` WRITE;
/*!40000 ALTER TABLE `beneficiary_profiles` DISABLE KEYS */;
INSERT INTO `beneficiary_profiles` VALUES (1,2,4,'Vegetarian','low','555-0102','2026-08-30 14:01:27','2026-08-30 14:01:27');
/*!40000 ALTER TABLE `beneficiary_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('5c785c036466adea360111aa28563bfd556b5fba','i:1;',1788127557),('5c785c036466adea360111aa28563bfd556b5fba:timer','i:1788127557;',1788127557);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Fruits & Vegetables','Fresh produce','2026-08-30 14:01:28','2026-08-30 14:01:28'),(2,'Bakery','Bread, pastries, baked goods','2026-08-30 14:01:28','2026-08-30 14:01:28'),(3,'Dairy','Milk, cheese, yogurt','2026-08-30 14:01:28','2026-08-30 14:01:28'),(4,'Prepared Meals','Ready-to-eat meals','2026-08-30 14:01:28','2026-08-30 14:01:28'),(5,'Canned Goods','Non-perishable canned items','2026-08-30 14:01:28','2026-08-30 14:01:28');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `delivery_schedules`
--

LOCK TABLES `delivery_schedules` WRITE;
/*!40000 ALTER TABLE `delivery_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `donation_items`
--

LOCK TABLES `donation_items` WRITE;
/*!40000 ALTER TABLE `donation_items` DISABLE KEYS */;
INSERT INTO `donation_items` VALUES (1,1,'Organic Carrots',4,'boxes','2026-08-30 14:01:28','2026-08-30 14:01:28'),(2,1,'Fresh Spinach',4,'boxes','2026-08-30 14:01:28','2026-08-30 14:01:28'),(3,1,'Ripe Tomatoes',4,'boxes','2026-08-30 14:01:28','2026-08-30 14:01:28');
/*!40000 ALTER TABLE `donation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (1,3,'Fresh seasonal vegetable boxes','Mixed boxes of carrots, leafy greens, tomatoes and capsicum. Produce is fresh and packed for same-day collection.',1,12,'boxes','2026-09-18','100 Community Way, Central District',NULL,'available',1,NULL,'2026-08-30 14:01:28','2026-08-30 14:01:28','food_bank'),(2,3,'Artisan bread and breakfast pastries','End-of-day bakery surplus packed in food-safe bags. Contains wheat, dairy and possible traces of nuts.',2,30,'items','2026-09-15','100 Community Way, Central District',NULL,'available',1,NULL,'2026-08-30 14:01:28','2026-08-30 14:01:28','food_bank'),(3,3,'Family-size prepared meals','Chilled vegetable pasta meals prepared today and sealed in labelled trays. Refrigerated collection required.',4,18,'trays','2026-09-16','100 Community Way, Central District',NULL,'available',1,NULL,'2026-08-30 14:01:28','2026-08-30 14:01:28','food_bank');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `food_requests`
--

LOCK TABLES `food_requests` WRITE;
/*!40000 ALTER TABLE `food_requests` DISABLE KEYS */;
INSERT INTO `food_requests` VALUES (1,2,'2026-08-30','pending','Food support requested for a household of four, with a preference for vegetarian meals and fresh produce.','2026-08-30 14:01:28','2026-08-30 14:01:28','home_delivery','456 Needy Ave',1,2,NULL,NULL,NULL);
/*!40000 ALTER TABLE `food_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,'Community Food Bank Central Distribution Hub, 100 Charity Lane',3.13900300,101.68685500,'donation',1,'2026-08-30 14:01:28','2026-08-30 14:01:28'),(2,'456 Needy Ave',3.14120000,101.69120000,'delivery',1,'2026-08-30 14:01:28','2026-08-30 14:01:28');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'0001_01_01_000003_create_jobs_queue_table',1),(5,'2024_01_01_000001_create_beneficiary_profiles_table',1),(6,'2024_01_01_000002_create_categories_table',1),(7,'2024_01_01_000003_create_donations_table',1),(8,'2024_01_01_000004_create_donation_items_table',1),(9,'2024_01_01_000005_create_food_requests_table',1),(10,'2024_01_01_000006_create_reservations_table',1),(11,'2024_01_01_000007_create_pickup_schedules_table',1),(12,'2024_01_01_000008_create_delivery_schedules_table',1),(13,'2024_01_01_000009_create_deliveries_table',1),(14,'2024_01_01_000010_create_locations_table',1),(15,'2024_01_01_000011_create_personal_access_tokens_table',1),(16,'2024_01_01_000012_create_notifications_table',1),(17,'2024_01_01_000013_add_fulfillment_details',1),(18,'2024_01_01_000014_add_requested_donation_and_schedule_to_food_requests',1),(19,'2024_01_01_000015_align_existing_requests_with_guided_workflow',1),(20,'2024_01_01_000016_move_all_donation_handoffs_to_food_bank',1),(21,'2024_01_01_000017_link_existing_notifications_to_food_requests',1),(22,'2024_01_01_000018_enforce_integer_quantities',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pickup_schedules`
--

LOCK TABLES `pickup_schedules` WRITE;
/*!40000 ALTER TABLE `pickup_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `pickup_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('AWNLrreQxrQgC1uv4eIBUJpqB5qNKOQZhjjEuE65',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUVc0bUVQcXM2bGFTZzdNTURERTFlM1J1Ym12MWJqS0JPRDdpWUNYVSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZGFzaGJvYXJkIjtzOjU6InJvdXRlIjtzOjk6ImRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==',1788127509);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@foodrescue.test','$2y$12$Dgv3Zj/JzUsGQatjQod5IuoD9nd01DNGoigJbd./7HngL6fNJ/T1S','admin','555-0100','123 Admin St',NULL,'2026-08-30 14:01:26','2026-08-30 14:01:26'),(2,'Jane Beneficiary','beneficiary@foodrescue.test','$2y$12$9qMAR3Af8SlzDwi2jX..0erXMAwjQU39oLnPdWD1g6ynmzdgLr3fu','beneficiary','555-0101','456 Needy Ave',NULL,'2026-08-30 14:01:27','2026-08-30 14:01:27'),(3,'John Donor','donor@foodrescue.test','$2y$12$zrqpwBcpQFnBUpOeOZIYY.7YVrA0ELLbTxheejUAbcZNK7OB.aMLm','donor','555-0103','789 Generous Blvd',NULL,'2026-08-30 14:01:27','2026-08-30 14:01:27'),(4,'Mike Driver','driver@foodrescue.test','$2y$12$xNAEUjOkNejoPVqJNU6I4eapMF0Jlexl97CHTevJq/0szg0yEvCae','driver','555-0104','321 Delivery Rd',NULL,'2026-08-30 14:01:28','2026-08-30 14:01:28');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-31  6:48:56
