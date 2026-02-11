# MindForge Community Module — Developer Quick Reference

**Last Updated**: February 10, 2026  
**For**: Backend & Frontend Developers  
**Duration**: 5-minute reference guide

---

## 🔧 Entity Usage Examples

### ChatMessage
```php
// Create a new message
$message = new ChatMessage();
$message->setContent('Hello team!');
$message->setSender($user);
$message->setVirtualRoom($room);

// Validate before saving
$errors = $validator->validate($message);
if (count($errors) > 0) {
    // Handle errors
} else {
    $em->persist($message);
    $em->flush();
}

// Access properties
$message->getId();           // int
$message->getContent();      // string
$message->getSender();       // User
$message->getVirtualRoom();  // VirtualRoom
$message->getCreatedAt();    // DateTimeImmutable
$message->isEdited();        // bool
```

### SharedTask
```php
// Send a challenge
$task = new SharedTask();
$task->setTitle('Complete Chapter 5 Exercises');
$task->setDescription('Maths assignment due tomorrow');
$task->setSharedBy($sender);
$task->setSharedWith($recipient);
$task->setStatus('pending'); // default

// Repository query
$incomingTasks = $repo->findBySharedWith($userId, 'pending');
$sentTasks = $repo->findBySharedBy($userId);

// Respond to challenge
$task->setStatus('accepted'); // or 'rejected'
$task->setRespondedAt(new \DateTimeImmutable());
```

### Claim
```php
// Create support ticket
$claim = new Claim();
$claim->setTitle('Cannot access resources');
$claim->setDescription('Detailed issue description...');
$claim->setPriority('high');      // low|medium|high|critical
$claim->setCreatedBy($user);
$claim->setStatus('open');        // default

// Admin actions
$claim->setStatus('in_progress');
$claim->setAssignedTo($adminUser);
$claim->setAdminNotes('Working on this issue');

// Resolve
$claim->setStatus('resolved');
$claim->setResolvedAt(new \DateTimeImmutable());

// Repository queries
$userClaims = $repo->findByUser($userId);
$openTickets = $repo->findOpenClaims();
$critical = $repo->findByStatusAndPriority('open', 'critical');
$count = $repo->countOpenClaims();
```

---

## 🛣️ Controller Methods Quick Reference

### Chat Routes
```php
// View room chat (paginated)
GET /community/room/{id}/chat?page=1

// Send message
POST /community/room/{id}/message/send
Input: content (1-5000 chars)

// Edit message
POST /community/message/{id}/edit
Input: content

// Delete message
POST /community/message/{id}/delete
```

### Challenge Routes
```php
// Send challenge form + process
GET/POST /community/challenge/send
Input (POST): title, description, shared_with_id

// View received challenges
GET /community/challenge/inbox?status=pending

// View sent challenges
GET /community/challenge/outbox?status=completed

// Respond to challenge
POST /community/challenge/{id}/respond
Input: response (accepted|rejected)
```

### Claim Routes
```php
// Create claim form + process
GET/POST /community/claim/create
Input (POST): title, description, priority

// View single claim
GET /community/claim/{id}

// List all user claims
GET /community/claim/list
```

### Admin Routes
```php
// List all claims
GET /admin/claims?status=open&priority=high

// Update claim status
POST /admin/claim/{id}/update-status
Input: status, priority, admin_notes

// Assign claim to admin
POST /admin/claim/{id}/assign
Input: assigned_to_id (optional)
```

---

## ✅ Validation Rules Reference

| Entity | Field | Rules | Example |
|--------|-------|-------|---------|
| ChatMessage | content | 1-5000 chars, not blank | "Hello team!" |
| SharedTask | title | 3-255 chars, not blank | "Complete Assignment" |
| SharedTask | description | max 2000 chars | "By tomorrow..." |
| SharedTask | status | pending\|accepted\|rejected\|completed | "pending" |
| Claim | title | 3-255 chars, not blank | "Bug Report" |
| Claim | description | 10-5000 chars, REQUIRED | "I cannot download..." |
| Claim | status | open\|in_progress\|resolved\|closed | "open" |
| Claim | priority | low\|medium\|high\|critical | "high" |
| Claim | admin_notes | max 5000 chars | "Investigating..." |

---

## 🗄️ Repository Query Examples

### ChatMessageRepository
```php
// Get paginated messages from a room
$messages = $repo->findByVirtualRoomPaginated($roomId, 50, 0);

// Count total messages in room
$count = $repo->countByVirtualRoom($roomId);

// Custom: Get recent messages
$qb = $repo->createQueryBuilder('cm')
    ->andWhere('cm.virtualRoom = :room')
    ->setParameter('room', $roomId)
    ->orderBy('cm.createdAt', 'DESC')
    ->setMaxResults(20);
return $qb->getQuery()->getResult();
```

### SharedTaskRepository
```php
// Find tasks sent TO a user
$received = $repo->findBySharedWith($userId, 'pending');

// Find tasks sent BY a user
$sent = $repo->findBySharedBy($userId, null);

// Custom: Find overdue (responded after 7 days)
$cutoff = (new \DateTime())->modify('-7 days');
// ... add your logic
```

### ClaimRepository
```php
// Get user's claims
$claims = $repo->findByUser($userId);

// Get all open claims (for admin)
$open = $repo->findOpenClaims();

// Filter by status and priority
$urgent = $repo->findByStatusAndPriority('open', 'critical');

// Count open for dashboard badge
$count = $repo->countOpenClaims(); // Returns: int
```

---

## 🎨 Template Snippets

### Form: Send Message
```html
<form method="POST" action="{{ path('community_message_send', {id: room.id}) }}">
    <textarea name="content" required minlength="1" maxlength="5000"></textarea>
    <button type="submit">Send</button>
</form>
```

### Form: Create Claim
```html
<form method="POST" action="{{ path('community_claim_create') }}">
    <input type="text" name="title" required minlength="3" maxlength="255" />
    <textarea name="description" required minlength="10" maxlength="5000"></textarea>
    <select name="priority">
        <option value="low">Low</option>
        <option value="medium" selected>Medium</option>
        <option value="high">High</option>
        <option value="critical">Critical</option>
    </select>
    <button type="submit">Create Ticket</button>
</form>
```

### Display: Message List
```html
{% for message in messages %}
    <div class="message">
        <strong>{{ message.sender.email }}</strong>
        <small>{{ message.createdAt|date('Y-m-d H:i') }}</small>
        <p>{{ message.content }}</p>
        {% if message.isEdited %}<em>Edited</em>{% endif %}
    </div>
{% endfor %}
```

### Display: Claims List
```html
<table>
    <tr>
        <th>Title</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Created</th>
    </tr>
    {% for claim in claims %}
        <tr>
            <td>{{ claim.title }}</td>
            <td><span class="status-{{ claim.status }}">{{ claim.status }}</span></td>
            <td><span class="priority-{{ claim.priority }}">{{ claim.priority }}</span></td>
            <td>{{ claim.createdAt|date('Y-m-d') }}</td>
        </tr>
    {% endfor %}
</table>
```

---

## 🚨 Common Issues & Solutions

### Issue: "Entity mapping not found"
**Solution**: Run migrations first
```bash
php bin/console doctrine:migrations:migrate
```

### Issue: "Validation failed: content cannot be blank"
**Solution**: All entities validate before persistence. Check form input:
```php
if (count($errors) > 0) {
    foreach ($errors as $error) {
        $this->addFlash('error', $error->getMessage());
    }
}
```

### Issue: "Access denied" when accessing claim
**Solution**: Check authorization in controller
```php
if ($claim->getCreatedBy() !== $user && !$this->isGranted('ROLE_ADMIN')) {
    throw $this->createAccessDeniedException();
}
```

### Issue: Messages not paginating
**Solution**: Use repository method:
```php
// NOT: $repo->findAll()
// YES:
$messages = $repo->findByVirtualRoomPaginated($roomId, 50, ($page-1)*50);
```

---

## 📊 Database Relationship Diagram

```
User (1) ──── (M) ChatMessage
User (1) ──── (M) SharedTask (as sender)
User (1) ──── (M) SharedTask (as recipient)
User (1) ──── (M) Claim (creator)
User (1) ──── (M) Claim (assigned to)

VirtualRoom (1) ──── (M) ChatMessage

User (1) ──── (M) VirtualRoom (creator)
User (M) ──── (M) VirtualRoom (participants)
```

---

## 🔐 Security Checklist for Developers

- [ ] Always validate user input server-side
- [ ] Check roles before admin operations
- [ ] Use parameterized queries (Doctrine does this)
- [ ] Check authorization (owner/admin only)
- [ ] Handle validation errors gracefully
- [ ] Never expose sensitive data in error messages
- [ ] Log security events (admin actions)

---

## 💾 Common Database Queries (SQL)

```sql
-- Get messages in a room (ordered newest first)
SELECT * FROM chat_message 
WHERE virtual_room_id = 1 
ORDER BY created_at DESC 
LIMIT 50;

-- Get pending challenges for a user
SELECT * FROM shared_task 
WHERE shared_with_id = 5 AND status = 'pending' 
ORDER BY created_at DESC;

-- Get open support tickets
SELECT * FROM claim 
WHERE status IN ('open', 'in_progress') 
ORDER BY priority DESC, created_at ASC;

-- Count messages by user
SELECT sender_id, COUNT(*) as message_count 
FROM chat_message 
GROUP BY sender_id 
ORDER BY message_count DESC;
```

---

## 🧪 Testing Template

```php
// Unit test example
public function testChatMessageValidation()
{
    $message = new ChatMessage();
    $message->setContent(''); // Empty = fail
    
    $errors = $this->validator->validate($message);
    $this->assertGreaterThan(0, count($errors));
}

// Functional test example
public function testSendMessage()
{
    $this->client->request('POST', '/community/room/1/message/send', [
        'content' => 'Hello'
    ]);
    
    $this->assertResponseIsSuccessful();
}
```

---

## 📞 Quick Help

**Documentation File**: `COMMUNITY_MODULE_GUIDE.md`  
**Summary File**: `COMMUNITY_MODULE_SUMMARY.md`  
**Example Templates**: `templates/community/*.html.twig`

**Key Files**:
- Entities: `src/Entity/Community/*.php`
- Controller: `src/Controller/Community/CommunityController.php`
- Repositories: `src/Repository/Community/*.php`

---

**Remember**: 
- ✓ Server-side validation ALWAYS
- ✓ Check permissions ALWAYS  
- ✓ Use repositories (not raw queries)
- ✓ Handle errors gracefully

**Questions?** Refer to `COMMUNITY_MODULE_GUIDE.md` for detailed documentation.
