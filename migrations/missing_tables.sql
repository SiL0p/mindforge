-- Create missing tables from mindforge_db dump

CREATE TABLE `application` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `opportunity_id` int(11) NOT NULL,
  `cover_letter` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `badge` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `criteria_type` varchar(50) NOT NULL,
  `criteria_value` int(11) NOT NULL,
  `rarity` varchar(20) DEFAULT 'common'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `focus_session` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `session_type` varchar(50) DEFAULT 'pomodoro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `user_badge` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_APPLICATION_USER` (`user_id`),
  ADD KEY `IDX_APPLICATION_OPPORTUNITY` (`opportunity_id`);

ALTER TABLE `badge`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `career_opportunity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_OPPORTUNITY_COMPANY` (`company_id`);

ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

ALTER TABLE `exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_EXAM_USER` (`user_id`),
  ADD KEY `IDX_EXAM_SUBJECT` (`subject_id`);

ALTER TABLE `focus_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_FOCUS_USER` (`user_id`),
  ADD KEY `IDX_FOCUS_TASK` (`task_id`),
  ADD KEY `IDX_FOCUS_STARTED` (`started_at`);

ALTER TABLE `gamification_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_GAMIFICATION_USER` (`user_id`);

ALTER TABLE `mentorship`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_MENTORSHIP_STUDENT` (`student_id`),
  ADD KEY `IDX_MENTORSHIP_MENTOR` (`mentor_id`),
  ADD KEY `IDX_MENTORSHIP_COMPANY` (`company_id`);

ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_TASK_USER` (`user_id`),
  ADD KEY `IDX_TASK_SUBJECT` (`subject_id`);

ALTER TABLE `user_badge`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_USER_BADGE` (`user_id`,`badge_id`),
  ADD KEY `IDX_USER_BADGE_USER` (`user_id`),
  ADD KEY `IDX_USER_BADGE_BADGE` (`badge_id`);

ALTER TABLE `application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `badge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `career_opportunity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `focus_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `gamification_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `mentorship`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_badge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `application`
  ADD CONSTRAINT `FK_APPLICATION_OPPORTUNITY` FOREIGN KEY (`opportunity_id`) REFERENCES `career_opportunity` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_APPLICATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `career_opportunity`
  ADD CONSTRAINT `FK_OPPORTUNITY_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE SET NULL;

ALTER TABLE `exam`
  ADD CONSTRAINT `FK_EXAM_SUBJECT` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_EXAM_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `focus_session`
  ADD CONSTRAINT `FK_FOCUS_TASK` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_FOCUS_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `gamification_stats`
  ADD CONSTRAINT `FK_GAMIFICATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `mentorship`
  ADD CONSTRAINT `FK_MENTORSHIP_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_MENTORSHIP_MENTOR` FOREIGN KEY (`mentor_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_MENTORSHIP_STUDENT` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `task`
  ADD CONSTRAINT `FK_TASK_SUBJECT` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_TASK_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_badge`
  ADD CONSTRAINT `FK_USER_BADGE_BADGE` FOREIGN KEY (`badge_id`) REFERENCES `badge` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_USER_BADGE_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
