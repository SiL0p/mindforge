-- ============================================
-- Seed data for Career module tables
-- ============================================

-- 1. ENTREPRISE (Companies)
INSERT INTO entreprise (id, name, description, industry, contact_email, contact_phone, website, created_at) VALUES
(1, 'TechCorp Solutions', 'A leading technology company specializing in cloud infrastructure and AI-driven applications. We build tools that empower businesses to scale efficiently.', 'Technology', 'hr@techcorp.com', '+1 415 555 0100', 'https://www.techcorp.com', '2025-09-15 10:00:00'),
(2, 'StartupInc', 'Fast-growing startup focused on fintech innovation. We are disrupting traditional banking with mobile-first solutions for the next generation.', 'Finance / Fintech', 'careers@startupinc.io', '+1 212 555 0200', 'https://www.startupinc.io', '2025-10-01 14:30:00'),
(3, 'InnovateLabs', 'Research-driven company at the intersection of healthcare and technology. We develop AI-powered diagnostic tools used by hospitals worldwide.', 'Healthcare / AI', 'jobs@innovatelabs.com', '+1 650 555 0300', 'https://www.innovatelabs.com', '2025-10-20 09:00:00'),
(4, 'GreenEnergy Co.', 'Sustainable energy company working on next-generation solar panel technology and smart grid solutions for residential and commercial use.', 'Energy / CleanTech', 'talent@greenenergy.co', '+1 303 555 0400', 'https://www.greenenergy.co', '2025-11-05 11:15:00'),
(5, 'DataWave Analytics', 'Big data consulting firm helping enterprises make sense of their data. We provide end-to-end analytics solutions from data warehousing to visualization.', 'Data Analytics', 'recruitment@datawave.io', '+1 512 555 0500', 'https://www.datawave.io', '2025-11-20 08:45:00');

-- 2. ENTREPRISE_USER (Link companies to company-role users)
-- User 5 (tech.corp@example.com) manages TechCorp
-- User 6 (startup.inc@example.com) manages StartupInc
-- User 7 (innovate.labs@example.com) manages InnovateLabs and GreenEnergy
INSERT INTO entreprise_user (entreprise_id, user_id) VALUES
(1, 5),
(2, 6),
(3, 7),
(4, 7),
(5, 5);

-- 3. OPPORTUNITE_CARRIERE (Job opportunities)
INSERT INTO opportunite_carriere (id, title, description, type, location, duration, deadline, status, created_at, company_id) VALUES
-- TechCorp opportunities
(1, 'Backend Developer Intern', 'Join our backend team to work on microservices architecture using Symfony and PostgreSQL. You will participate in code reviews, write unit tests, and deploy to production environments. Ideal for students with basic PHP knowledge looking to gain real-world experience.', 'internship', 'San Francisco, CA', '6 months', '2026-04-30', 'active', '2026-01-10 09:00:00', 1),
(2, 'Full-Stack Developer', 'We are looking for a full-stack developer proficient in React and Symfony. You will own features end-to-end, from database design to frontend implementation. Strong communication skills and experience with agile methodologies are a plus.', 'fulltime', 'San Francisco, CA (Hybrid)', NULL, '2026-05-15', 'active', '2026-01-15 14:00:00', 1),
(3, 'DevOps Apprentice', 'Learn infrastructure management, CI/CD pipelines, and cloud deployment with our DevOps team. You will work with Docker, Kubernetes, and AWS. This apprenticeship is designed to transition into a full-time role upon successful completion.', 'apprenticeship', 'Remote', '12 months', '2026-03-31', 'active', '2026-01-20 10:30:00', 1),

-- StartupInc opportunities
(4, 'Mobile Developer Intern (React Native)', 'Help us build the next version of our mobile banking app used by over 500K users. You will implement new features, fix bugs, and optimize performance. Experience with React Native or Flutter is preferred but not required.', 'internship', 'New York, NY', '4 months', '2026-04-15', 'active', '2026-01-12 11:00:00', 2),
(5, 'Data Analyst - Part Time', 'Analyze user behavior data to drive product decisions. Create dashboards, run A/B test analyses, and present findings to stakeholders. Proficiency in SQL and Python is required. Flexible hours, perfect for students.', 'parttime', 'New York, NY (Hybrid)', NULL, '2026-05-01', 'active', '2026-01-25 16:00:00', 2),
(6, 'UX/UI Designer Freelance', 'Redesign our onboarding flow and key user journeys. Deliver wireframes, prototypes, and final designs in Figma. This is a project-based engagement with potential for ongoing collaboration.', 'freelance', 'Remote', '2 months', '2026-03-15', 'active', '2026-02-01 09:30:00', 2),

-- InnovateLabs opportunities
(7, 'Machine Learning Research Intern', 'Work alongside our research scientists on cutting-edge medical imaging AI models. You will preprocess datasets, train neural networks, and evaluate model performance. Strong Python and PyTorch skills required.', 'internship', 'Palo Alto, CA', '6 months', '2026-04-01', 'active', '2026-01-18 08:00:00', 3),
(8, 'Software Engineer - Healthcare Platform', 'Build and maintain our HIPAA-compliant healthcare platform. Work with Node.js, React, and PostgreSQL. Experience with healthcare data standards (HL7/FHIR) is a bonus. Competitive salary and benefits.', 'fulltime', 'Palo Alto, CA', NULL, '2026-06-01', 'active', '2026-02-05 13:00:00', 3),

-- GreenEnergy opportunities
(9, 'Embedded Systems Intern', 'Program firmware for IoT sensors used in smart grid monitoring. Work with C/C++ on ARM microcontrollers. You will also help design PCB layouts and run hardware tests. Great opportunity for EE students.', 'internship', 'Denver, CO', '3 months', '2026-03-30', 'active', '2026-01-22 10:00:00', 4),
(10, 'Sustainability Analyst Apprentice', 'Support our sustainability team in measuring carbon footprint reductions across projects. Use data modeling to forecast energy savings and prepare reports for stakeholders. Interest in environmental science is essential.', 'apprenticeship', 'Denver, CO (Hybrid)', '8 months', '2026-04-20', 'active', '2026-02-08 11:30:00', 4),

-- DataWave opportunities
(11, 'Junior Data Engineer', 'Design and maintain ETL pipelines using Apache Airflow and dbt. Work with Snowflake and BigQuery data warehouses. This role bridges software engineering and data analytics in a fast-paced consulting environment.', 'fulltime', 'Austin, TX', NULL, '2026-05-30', 'active', '2026-02-01 10:00:00', 5),
(12, 'Business Intelligence Intern', 'Create interactive Tableau and Power BI dashboards for our enterprise clients. Learn data storytelling, work directly with client teams, and gain exposure to real consulting projects across multiple industries.', 'internship', 'Austin, TX (Hybrid)', '5 months', '2026-04-10', 'active', '2026-02-10 15:00:00', 5),

-- One closed opportunity
(13, 'Frontend Developer (Contract)', 'Short-term contract to rebuild our marketing website using Next.js and Tailwind CSS. This opportunity has been filled.', 'freelance', 'Remote', '2 months', '2026-01-15', 'closed', '2025-12-01 09:00:00', 1);

-- 4. DEMANDE (Applications from students)
INSERT INTO demande (id, cover_letter, status, applied_at, user_id, opportunity_id) VALUES
-- Student user 1 (id=1, a@a.a) applications
(1, 'I am passionate about backend development and have been learning Symfony for the past year through personal projects. I built a REST API for a task management app and would love the chance to apply my skills in a professional setting at TechCorp.', 'pending', '2026-02-01 10:30:00', 1, 1),
(2, 'As a data enthusiast, I have completed several online courses in SQL and Python analytics. I am eager to apply my knowledge in a real-world fintech environment and contribute to data-driven decision making at StartupInc.', 'pending', '2026-02-05 14:00:00', 1, 5),

-- Student user 2 (id=2, student1@mindforge.local) applications
(3, 'I have strong experience with React Native from building two personal apps published on the App Store. I am excited about the opportunity to work on a product with half a million users and learn from an experienced mobile team.', 'reviewed', '2026-01-28 09:15:00', 2, 4),
(4, 'My background in embedded systems from university coursework makes me a great fit for this role. I have programmed ARM Cortex-M4 boards and worked on IoT sensor projects in my electronics lab.', 'accepted', '2026-01-30 11:00:00', 2, 9),

-- Student user 3 (id=3, student2@mindforge.local) applications
(5, 'I am a machine learning enthusiast with hands-on experience in PyTorch and TensorFlow. My senior thesis focused on image classification for plant disease detection, which aligns well with your medical imaging AI research.', 'pending', '2026-02-10 16:45:00', 3, 7),
(6, 'I have experience with DevOps tools from maintaining CI/CD pipelines for my university capstone project. I used GitHub Actions, Docker, and deployed to AWS EC2. Eager to deepen my skills in a professional apprenticeship.', 'rejected', '2026-01-25 08:30:00', 3, 3),

-- Student user 8 (id=8, student1@example.com) applications
(7, 'I am currently studying data engineering and have built ETL pipelines using Python and Apache Airflow for academic projects. I would love to bring my technical skills to DataWave and learn from experienced consultants.', 'pending', '2026-02-12 10:00:00', 8, 11),
(8, 'With strong skills in Tableau and SQL, I am confident I can contribute immediately to your BI consulting projects. I have created dashboards for student organizations that tracked engagement across 2000+ members.', 'accepted', '2026-02-08 13:20:00', 8, 12),

-- Student user 9 (id=9, student2@example.com) applications
(9, 'I am a full-stack developer with 2 years of experience building web applications with React and Node.js. I am looking to transition into the Symfony ecosystem and believe TechCorp is the perfect place to grow.', 'pending', '2026-02-11 09:00:00', 9, 2),
(10, 'Sustainability is my passion. I have volunteered with environmental NGOs and have strong analytical skills from my economics background. I would be thrilled to combine data analysis with environmental impact at GreenEnergy.', 'reviewed', '2026-02-09 15:30:00', 9, 10),

-- Student user 10 (id=10, student3@example.com) applications
(11, 'I am a UX design student with a portfolio of mobile app redesigns. I have experience with Figma, user research, and usability testing. I would love to take on the challenge of improving StartupInc onboarding experience.', 'pending', '2026-02-13 11:00:00', 10, 6),
(12, 'As a computer science student with a focus on healthcare IT, I have studied HL7 and FHIR standards. Building HIPAA-compliant software is exactly the kind of challenge I am looking for in my career.', 'pending', '2026-02-12 14:15:00', 10, 8);
