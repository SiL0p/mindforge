-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 09 fév. 2026 à 19:44
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mindforge_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `application`
--

CREATE TABLE `application` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `opportunity_id` int(11) NOT NULL,
  `cover_letter` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `badge`
--

CREATE TABLE `badge` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `criteria_type` varchar(50) NOT NULL,
  `criteria_value` int(11) NOT NULL,
  `rarity` varchar(20) DEFAULT 'common'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `career_opportunity`
--

CREATE TABLE `career_opportunity` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `type` varchar(50) DEFAULT 'internship',
  `location` varchar(255) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `chat_message`
--

CREATE TABLE `chat_message` (
  `id` int(11) NOT NULL,
  `virtual_room_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_edited` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `claim`
--

CREATE TABLE `claim` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `category` varchar(50) NOT NULL,
  `status` varchar(20) DEFAULT 'open',
  `priority` varchar(20) DEFAULT 'medium',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `contact_email` varchar(180) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20240208120000', '2026-02-08 17:57:37', 381);

-- --------------------------------------------------------

--
-- Structure de la table `exam`
--

CREATE TABLE `exam` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `exam_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `priority` int(11) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `focus_session`
--

CREATE TABLE `focus_session` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `session_type` varchar(50) DEFAULT 'pomodoro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gamification_stats`
--

CREATE TABLE `gamification_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_xp` int(11) NOT NULL DEFAULT 0,
  `current_level` int(11) NOT NULL DEFAULT 1,
  `streak_days` int(11) NOT NULL DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `total_focus_time` int(11) NOT NULL DEFAULT 0,
  `tasks_completed` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mentorship`
--

CREATE TABLE `mentorship` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ended_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `profile`
--

CREATE TABLE `profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` longtext DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'UTC',
  `locale` varchar(10) DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resource`
--

CREATE TABLE `resource` (
  `id` int(11) NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `rating` smallint(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role_request`
--

CREATE TABLE `role_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `motivation` longtext NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `admin_notes` longtext DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `shared_task`
--

CREATE TABLE `shared_task` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `shared_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subject`
--

CREATE TABLE `subject` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` longtext DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6840d6',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task`
--

CREATE TABLE `task` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `difficulty` varchar(20) DEFAULT 'medium',
  `status` varchar(20) DEFAULT 'todo',
  `due_date` datetime DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_badge`
--

CREATE TABLE `user_badge` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `virtual_room`
--

CREATE TABLE `virtual_room` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `max_participants` int(11) NOT NULL DEFAULT 10,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `virtual_room_participants`
--

CREATE TABLE `virtual_room_participants` (
  `virtual_room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_APPLICATION_USER` (`user_id`),
  ADD KEY `IDX_APPLICATION_OPPORTUNITY` (`opportunity_id`);

--
-- Index pour la table `badge`
--
ALTER TABLE `badge`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `career_opportunity`
--
ALTER TABLE `career_opportunity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_OPPORTUNITY_COMPANY` (`company_id`);

--
-- Index pour la table `chat_message`
--
ALTER TABLE `chat_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_CHAT_ROOM` (`virtual_room_id`),
  ADD KEY `IDX_CHAT_SENDER` (`sender_id`),
  ADD KEY `IDX_CHAT_TIME` (`sent_at`);

--
-- Index pour la table `claim`
--
ALTER TABLE `claim`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_CLAIM_USER` (`user_id`),
  ADD KEY `IDX_CLAIM_STATUS` (`status`);

--
-- Index pour la table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_EXAM_USER` (`user_id`),
  ADD KEY `IDX_EXAM_SUBJECT` (`subject_id`);

--
-- Index pour la table `focus_session`
--
ALTER TABLE `focus_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_FOCUS_USER` (`user_id`),
  ADD KEY `IDX_FOCUS_TASK` (`task_id`),
  ADD KEY `IDX_FOCUS_STARTED` (`started_at`);

--
-- Index pour la table `gamification_stats`
--
ALTER TABLE `gamification_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_GAMIFICATION_USER` (`user_id`);

--
-- Index pour la table `mentorship`
--
ALTER TABLE `mentorship`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_MENTORSHIP_STUDENT` (`student_id`),
  ADD KEY `IDX_MENTORSHIP_MENTOR` (`mentor_id`),
  ADD KEY `IDX_MENTORSHIP_COMPANY` (`company_id`);

--
-- Index pour la table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_PROFILE_USER` (`user_id`);

--
-- Index pour la table `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_RESOURCE_UPLOADER` (`uploader_id`),
  ADD KEY `IDX_RESOURCE_SUBJECT` (`subject_id`),
  ADD KEY `IDX_RESOURCE_TYPE` (`type`);

--
-- Index pour la table `role_request`
--
ALTER TABLE `role_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ROLE_REQUEST_USER` (`user_id`);

--
-- Index pour la table `shared_task`
--
ALTER TABLE `shared_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_SHARED_TASK` (`task_id`),
  ADD KEY `IDX_SHARED_SENDER` (`sender_id`),
  ADD KEY `IDX_SHARED_RECIPIENT` (`recipient_id`);

--
-- Index pour la table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_SUBJECT_CODE` (`code`);

--
-- Index pour la table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_TASK_USER` (`user_id`),
  ADD KEY `IDX_TASK_SUBJECT` (`subject_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- Index pour la table `user_badge`
--
ALTER TABLE `user_badge`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_USER_BADGE` (`user_id`,`badge_id`),
  ADD KEY `IDX_USER_BADGE_USER` (`user_id`),
  ADD KEY `IDX_USER_BADGE_BADGE` (`badge_id`);

--
-- Index pour la table `virtual_room`
--
ALTER TABLE `virtual_room`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ROOM_CREATOR` (`creator_id`),
  ADD KEY `IDX_ROOM_SUBJECT` (`subject_id`),
  ADD KEY `IDX_ROOM_ACTIVE` (`is_active`);

--
-- Index pour la table `virtual_room_participants`
--
ALTER TABLE `virtual_room_participants`
  ADD PRIMARY KEY (`virtual_room_id`,`user_id`),
  ADD KEY `FK_PARTICIPANTS_USER` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `application`
--
ALTER TABLE `application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `badge`
--
ALTER TABLE `badge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `career_opportunity`
--
ALTER TABLE `career_opportunity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `chat_message`
--
ALTER TABLE `chat_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `claim`
--
ALTER TABLE `claim`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exam`
--
ALTER TABLE `exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `focus_session`
--
ALTER TABLE `focus_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gamification_stats`
--
ALTER TABLE `gamification_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mentorship`
--
ALTER TABLE `mentorship`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `profile`
--
ALTER TABLE `profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `resource`
--
ALTER TABLE `resource`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `role_request`
--
ALTER TABLE `role_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `shared_task`
--
ALTER TABLE `shared_task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `subject`
--
ALTER TABLE `subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task`
--
ALTER TABLE `task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_badge`
--
ALTER TABLE `user_badge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `virtual_room`
--
ALTER TABLE `virtual_room`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `FK_APPLICATION_OPPORTUNITY` FOREIGN KEY (`opportunity_id`) REFERENCES `career_opportunity` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_APPLICATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `career_opportunity`
--
ALTER TABLE `career_opportunity`
  ADD CONSTRAINT `FK_OPPORTUNITY_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `chat_message`
--
ALTER TABLE `chat_message`
  ADD CONSTRAINT `FK_CHAT_ROOM` FOREIGN KEY (`virtual_room_id`) REFERENCES `virtual_room` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_CHAT_SENDER` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `claim`
--
ALTER TABLE `claim`
  ADD CONSTRAINT `FK_CLAIM_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exam`
--
ALTER TABLE `exam`
  ADD CONSTRAINT `FK_EXAM_SUBJECT` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_EXAM_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `focus_session`
--
ALTER TABLE `focus_session`
  ADD CONSTRAINT `FK_FOCUS_TASK` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_FOCUS_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gamification_stats`
--
ALTER TABLE `gamification_stats`
  ADD CONSTRAINT `FK_GAMIFICATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mentorship`
--
ALTER TABLE `mentorship`
  ADD CONSTRAINT `FK_MENTORSHIP_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_MENTORSHIP_MENTOR` FOREIGN KEY (`mentor_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_MENTORSHIP_STUDENT` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `FK_PROFILE_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `resource`
--
ALTER TABLE `resource`
  ADD CONSTRAINT `FK_RESOURCE_SUBJECT` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_RESOURCE_UPLOADER` FOREIGN KEY (`uploader_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `role_request`
--
ALTER TABLE `role_request`
  ADD CONSTRAINT `FK_ROLE_REQUEST_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `shared_task`
--
ALTER TABLE `shared_task`
  ADD CONSTRAINT `FK_SHARED_RECIPIENT` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_SHARED_SENDER` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_SHARED_TASK` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `FK_TASK_SUBJECT` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_TASK_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_badge`
--
ALTER TABLE `user_badge`
  ADD CONSTRAINT `FK_USER_BADGE_BADGE` FOREIGN KEY (`badge_id`) REFERENCES `badge` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_USER_BADGE_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `virtual_room`
--
ALTER TABLE `virtual_room`
  ADD CONSTRAINT `FK_ROOM_CREATOR` FOREIGN KEY (`creator_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ROOM_SUBJECT` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `virtual_room_participants`
--
ALTER TABLE `virtual_room_participants`
  ADD CONSTRAINT `FK_PARTICIPANTS_ROOM` FOREIGN KEY (`virtual_room_id`) REFERENCES `virtual_room` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_PARTICIPANTS_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
