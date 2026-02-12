# MindForge Community Module (M5) — Implementation Guide

## 📋 Overview
The Community Module implements three core features for MindForge:
1. **Real-time Chat** inside Virtual Rooms
2. **Shared Tasks** (Challenges) between friends
3. **Claims** (Support Ticket System)

All features include **server-side validation** using Symfony Assertions (no front-end validation required).

---

## 🏗️ Entity Architecture

### 1. ChatMessage Entity
**Purpose**: Messages sent in Virtual Rooms for real-time collaboration.

**Table**: `chat_message`
```
┌─────────────────────────────────────┐
│ ChatMessage                         │
├─────────────────────────────────────┤
│ id: INTEGER (PK)                    │
│ content: LONGTEXT (Required)        │
│ is_edited: BOOLEAN (default: false) │
│ created_at: DATETIME IMMUTABLE      │
│ edited_at: DATETIME IMMUTABLE       │
│ sender_id: INTEGER (FK → User)      │
│ virtual_room_id: INTEGER (FK)       │
└─────────────────────────────────────┘
```

**Relationships**:
- **ManyToOne → User** (via `sender`)
- **ManyToOne → VirtualRoom** (via `virtualRoom`)

**Validations**:
- `content`: Not blank, 1-5000 characters
- `sender`: Required (FK constraint)
- `virtualRoom`: Required (FK constraint)

**Key Methods**:
- `getContent()`, `setContent()`
- `getSender()`, `setSender()`
- `getVirtualRoom()`, `setVirtualRoom()`
- `isEdited()`, `setIsEdited()`
- `getCreatedAt()`, `getEditedAt()`

---

### 2. SharedTask Entity
**Purpose**: Send challenges/tasks to friends for collaborative learning.

**Table**: `shared_task`
```
┌──────────────────────────────────────┐
│ SharedTask                           │
├──────────────────────────────────────┤
│ id: INTEGER (PK)                     │
│ title: VARCHAR(255) (Required)       │
│ description: LONGTEXT (Optional)     │
│ status: VARCHAR(50)                  │
│   - pending (default)                │
│   - accepted                         │
│   - rejected                         │
│   - completed                        │
│ created_at: DATETIME IMMUTABLE       │
│ responded_at: DATETIME IMMUTABLE     │
│ shared_by_id: INTEGER (FK → User)    │
│ shared_with_id: INTEGER (FK → User)  │
└──────────────────────────────────────┘
```

**Relationships**:
- **ManyToOne → User** (via `sharedBy` - Sender)
- **ManyToOne → User** (via `sharedWith` - Recipient)
- **Future**: OneToMany → Task (once Planner module is ready)

**Validations**:
- `title`: Not blank, 3-255 characters
- `description`: Optional, max 2000 characters
- `status`: Must be one of ['pending', 'accepted', 'rejected', 'completed']
- `sharedBy`: Required (FK constraint)
- `sharedWith`: Required (FK constraint)

**Key Methods**:
- `getTitle()`, `setTitle()`
- `getDescription()`, `setDescription()`
- `getStatus()`, `setStatus()`
- `getSharedBy()`, `setSharedBy()`
- `getSharedWith()`, `setSharedWith()`
- `getCreateAt()`, `getRespondedAt()`, `setRespondedAt()`

---

### 3. Claim Entity
**Purpose**: Support ticket system for users to report issues and admins to manage them.

**Table**: `claim`
```
┌──────────────────────────────────────┐
│ Claim                                │
├──────────────────────────────────────┤
│ id: INTEGER (PK)                     │
│ title: VARCHAR(255) (Required)       │
│ description: LONGTEXT (Required)     │
│ status: VARCHAR(50)                  │
│   - open (default)                   │
│   - in_progress                      │
│   - resolved                         │
│   - closed                           │
│ priority: VARCHAR(50)                │
│   - low                              │
│   - medium (default)                 │
│   - high                             │
│   - critical                         │
│ admin_notes: LONGTEXT (Optional)     │
│ created_at: DATETIME IMMUTABLE       │
│ updated_at: DATETIME IMMUTABLE       │
│ resolved_at: DATETIME IMMUTABLE      │
│ created_by_id: INTEGER (FK → User)   │
│ assigned_to_id: INTEGER (FK → User)  │
└──────────────────────────────────────┘
```

**Relationships**:
- **ManyToOne → User** (via `createdBy` - Issue Creator)
- **ManyToOne → User** (via `assignedTo` - Admin Handler, Nullable)

**Validations**:
- `title`: Not blank, 3-255 characters
- `description`: Not blank, 10-5000 characters
- `status`: Must be one of ['open', 'in_progress', 'resolved', 'closed']
- `priority`: Must be one of ['low', 'medium', 'high', 'critical']
- `adminNotes`: Optional, max 5000 characters
- `createdBy`: Required (FK constraint)
- `assignedTo`: Optional (can be unassigned initially)

**Key Methods**:
- `getTitle()`, `setTitle()`
- `getDescription()`, `setDescription()`
- `getStatus()`, `setStatus()`
- `getPriority()`, `setPriority()`
- `getAdminNotes()`, `setAdminNotes()`
- `getCreatedBy()`, `setCreatedBy()`
- `getAssignedTo()`, `setAssignedTo()`
- `getResolvedAt()`, `setResolvedAt()`

---

## 🔌 Data Model Relationships

```
User (Architect Module)
├── chatMessages (One-to-Many) → ChatMessage
├── createdClaims (One-to-Many) → Claim
├── assignedClaims (One-to-Many) → Claim
├── sharedTasksSent (One-to-Many) → SharedTask
├── sharedTasksReceived (One-to-Many) → SharedTask
├── createdRooms (One-to-Many) → VirtualRoom [Guardian]
├── joinedRooms (Many-to-Many) → VirtualRoom [Guardian]
└── uploadedResources (One-to-Many) → Resource [Guardian]

VirtualRoom (Guardian Module)
└── chatMessages (One-to-Many) → ChatMessage

ChatMessage
├── sender (Many-to-One) → User
└── virtualRoom (Many-to-One) → VirtualRoom

SharedTask
├── sharedBy (Many-to-One) → User
└── sharedWith (Many-to-One) → User

Claim
├── createdBy (Many-to-One) → User
└── assignedTo (Many-to-One) → User (Optional)
```

---

## 🎯 Controller Methods & Routes

### Chat Message Routes

#### View Room Chat
```
GET /community/room/{id}/chat
Parameters:
  - id: Room ID (required)
  - page: Page number (optional, default: 1)
Response: Paginated messages (50 per page)
Auth: Requires authenticated user
Template: community/room_chat.html.twig
```

#### Send Message
```
POST /community/room/{id}/message/send
Parameters:
  - id: Room ID (required)
Data:
  - content: Message text (required, 1-5000 chars)
Response: Redirect to room chat with success/error flash
Auth: Requires authenticated user
Validation: Server-side only (Symfony Assertions)
```

#### Edit Message
```
POST /community/message/{id}/edit
Parameters:
  - id: Message ID (required)
Data:
  - content: New message text (required)
Response: Redirect to room chat
Auth: Only message sender or admin
Validation: Server-side only
```

#### Delete Message
```
POST /community/message/{id}/delete
Parameters:
  - id: Message ID (required)
Response: Redirect to room chat
Auth: Only message sender or admin
```

---

### Shared Task Routes

#### Send Challenge
```
GET/POST /community/challenge/send
GET: Show challenge form with available users
POST Data:
  - title: Challenge title (required, 3-255 chars)
  - description: Challenge description (optional)
  - shared_with_id: Recipient user ID (required)
Response: GET returns form, POST redirects to inbox
Auth: Requires authenticated user
Validation: Server-side only
```

#### View Inbox (Received Challenges)
```
GET /community/challenge/inbox
Parameters:
  - status: Filter by status (optional)
Response: List of challenges sent to user
Auth: Requires authenticated user
Template: community/challenge_inbox.html.twig
```

#### View Outbox (Sent Challenges)
```
GET /community/challenge/outbox
Parameters:
  - status: Filter by status (optional)
Response: List of challenges sent by user
Auth: Requires authenticated user
Template: community/challenge_outbox.html.twig
```

#### Respond to Challenge
```
POST /community/challenge/{id}/respond
Parameters:
  - id: Task ID (required)
Data:
  - response: 'accepted' or 'rejected' (required)
Response: Redirect to inbox
Auth: Only task recipient
```

---

### Claim Routes

#### Create Claim
```
GET/POST /community/claim/create
GET: Show claim creation form
POST Data:
  - title: Claim title (required, 3-255 chars)
  - description: Issue description (required, 10-5000 chars)
  - priority: Priority level (required)
Response: GET returns form, POST redirects to list
Auth: Requires authenticated user
Validation: Server-side only
```

#### View Claim
```
GET /community/claim/{id}
Parameters:
  - id: Claim ID (required)
Response: Claim details
Auth: Only creator or admin
Template: community/view_claim.html.twig
```

#### List User Claims
```
GET /community/claim/list
Response: All claims created by user
Auth: Requires authenticated user
Template: community/claims_list.html.twig
```

---

### Admin Routes (Backoffice)

#### List All Claims (Admin)
```
GET /admin/claims
Parameters:
  - status: Filter by status (optional)
  - priority: Filter by priority (optional)
Response: All open/filtered claims
Auth: Requires ROLE_ADMIN
Template: admin/community_claims.html.twig
Status codes: open, in_progress, resolved, closed
```

#### Update Claim Status (Admin)
```
POST /admin/claim/{id}/update-status
Parameters:
  - id: Claim ID (required)
Data:
  - status: New status (required)
  - priority: New priority (required)
  - admin_notes: Admin notes (optional)
Response: Redirect to claims list
Auth: Requires ROLE_ADMIN
Validation: Status and priority must be valid choices
```

#### Assign Claim (Admin)
```
POST /admin/claim/{id}/assign
Parameters:
  - id: Claim ID (required)
Data:
  - assigned_to_id: Admin/support user ID (optional, can be null)
Response: Redirect to claims list
Auth: Requires ROLE_ADMIN
```

---

## 🔐 Server-Side Validation

All validation is performed using **Symfony Assertions** on the server side. No JavaScript validation is used.

### Validation Examples

**ChatMessage**:
```php
#[Assert\NotBlank(message: 'Le message ne peut pas être vide.')]
#[Assert\Length(
    min: 1,
    max: 5000,
    maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères.'
)]
private ?string $content = null;
```

**SharedTask**:
```php
#[Assert\NotBlank(message: 'Le titre du défi ne peut pas être vide.')]
#[Assert\Length(
    min: 3,
    max: 255,
    minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
    maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
)]
#[Assert\Choice(
    choices: ['pending', 'accepted', 'rejected', 'completed'],
    message: 'Le statut doit être parmi les valeurs acceptées.'
)]
private string $status = 'pending';
```

**Claim**:
```php
#[Assert\NotBlank(message: 'Le titre du ticket ne peut pas être vide.')]
#[Assert\Length(
    min: 10,
    max: 5000,
    minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
)]
```

---

## 📊 Repository Methods

### ChatMessageRepository
- `findByVirtualRoomPaginated($roomId, $limit, $offset)` - Get paginated messages
- `countByVirtualRoom($roomId)` - Count total messages in room

### SharedTaskRepository
- `findBySharedWith($userId, $status)` - Find tasks sent to user
- `findBySharedBy($userId, $status)` - Find tasks sent by user

### ClaimRepository
- `findByUser($userId)` - Get user's claims
- `findOpenClaims()` - Get all open/in-progress claims
- `findByStatusAndPriority($status, $priority)` - Filter claims
- `countOpenClaims()` - Count open claims for dashboard

---

## 🚀 Database Setup

### Step 1: Generate Migration
```bash
cd c:\Users\youss\Desktop\yy\mindforge
php bin/console make:migration
```

The migration file is already created at: `migrations/Version20260210120000.php`

### Step 2: Execute Migration
```bash
php bin/console doctrine:migrations:migrate
```

This will create three new tables:
- `chat_message`
- `shared_task`
- `claim`

---

## 🔄 Integration with Java Desktop App

The Java Desktop App can access Community data via REST API endpoints (to be implemented). Database structure is compatible with JDBC/Hibernate:

### ChatMessage (JDBC Query Example)
```java
// Fetch messages from a Virtual Room
SELECT * FROM chat_message 
WHERE virtual_room_id = ? 
ORDER BY created_at DESC 
LIMIT 50;
```

### SharedTask (JDBC Query Example)
```java
// Fetch tasks received by a user
SELECT * FROM shared_task 
WHERE shared_with_id = ? 
AND status = 'pending' 
ORDER BY created_at DESC;
```

### Claim (JDBC Query Example)
```java
// Fetch user's claims
SELECT * FROM claim 
WHERE created_by_id = ? 
ORDER BY created_at DESC;
```

---

## 📝 Usage Examples

### Example 1: Send a Chat Message
```php
// In controller
$chatMessage = new ChatMessage();
$chatMessage->setContent('Hello team!');
$chatMessage->setSender($currentUser);
$chatMessage->setVirtualRoom($virtualRoom);

$errors = $validator->validate($chatMessage);
if (count($errors) > 0) {
    // Handle validation errors
} else {
    $em->persist($chatMessage);
    $em->flush();
}
```

### Example 2: Send a Challenge
```php
// In controller
$sharedTask = new SharedTask();
$sharedTask->setTitle('Complete Maths Assignment');
$sharedTask->setDescription('Chapter 5 exercises');
$sharedTask->setSharedBy($currentUser);
$sharedTask->setSharedWith($friendUser);

$errors = $validator->validate($sharedTask);
if (count($errors) > 0) {
    // Handle validation errors
} else {
    $em->persist($sharedTask);
    $em->flush();
}
```

### Example 3: Create a Support Ticket
```php
// In controller
$claim = new Claim();
$claim->setTitle('Cannot access resources');
$claim->setDescription('When I try to download a PDF, I get a 404 error...');
$claim->setPriority('high');
$claim->setCreatedBy($currentUser);

$errors = $validator->validate($claim);
if (count($errors) > 0) {
    // Handle validation errors
} else {
    $em->persist($claim);
    $em->flush(); // Status is 'open' by default
}
```

---

## 🛠️ File Structure

```
src/
├── Entity/Community/
│   ├── ChatMessage.php
│   ├── Claim.php
│   └── SharedTask.php
├── Repository/Community/
│   ├── ChatMessageRepository.php
│   ├── ClaimRepository.php
│   └── SharedTaskRepository.php
├── Controller/Community/
│   └── CommunityController.php
└── Entity/Architect/
    └── User.php (Updated with relationships)

migrations/
└── Version20260210120000.php

templates/community/
├── room_chat.html.twig
├── send_challenge.html.twig
├── challenge_inbox.html.twig
├── challenge_outbox.html.twig
├── create_claim.html.twig
├── view_claim.html.twig
└── claims_list.html.twig

templates/admin/
└── community_claims.html.twig
```

---

## ✅ Checklist for Completion

- [x] Create ChatMessage entity with validations
- [x] Create SharedTask entity with validations
- [x] Create Claim entity with validations
- [x] Create Repository classes with custom methods
- [x] Create Community Controller with all CRUD methods
- [x] Update VirtualRoom entity with ChatMessage relationship
- [x] Update User entity with all relationships
- [x] Create database migration
- [ ] Create Twig templates (pending)
- [ ] Create CSS styling (pending)
- [ ] Create REST API endpoints for Java Desktop App (pending)
- [ ] Implement real-time WebSocket chat (optional enhancement)

---

## 🎨 Design Notes

**Theme Alignment**: 
- Use dark mode (#0f172a background)
- Primary violet (#6840d6) for headers
- Secondary blue (#2e65d9) for action buttons
- Accent magenta (#af17c2) for highlights

**Messages**:
- French validation messages (user-friendly)
- Clear error feedback via Flash messages
- Success confirmation for all actions

**Permissions**:
- Students can: view rooms, send messages, create claims, send challenges
- Student+: all above + create virtual rooms, upload resources
- Admin: manage all claims, assign support tickets, update any status

---

## 📞 Support & Questions

For implementation questions, refer to:
- Entity files for exact validation rules
- Repository files for database queries
- Controller methods for business logic
- Migration file for database schema

---

**Last Updated**: February 10, 2026
**Module Status**: Ready for Integration
**Version**: 1.0
