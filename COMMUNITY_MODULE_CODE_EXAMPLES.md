# MindForge Community Module — PHP Code Examples & Troubleshooting

**Updated**: February 10, 2026

---

## 📝 Complete PHP Code Examples

### Example 1: Send Chat Message with Validation

```php
// In CommunityController
#[Route('/room/{id}/message/send', name: 'community_message_send', methods: ['POST'])]
public function sendMessage(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    ValidatorInterface $validator
): Response {
    $virtualRoom = $em->getRepository(VirtualRoom::class)->find($id);
    $user = $this->getUser();

    if (!$virtualRoom || !$user) {
        $this->addFlash('error', 'Salle ou utilisateur introuvable.');
        return $this->redirectToRoute('user_dashboard');
    }

    // Get and trim content
    $content = trim($request->request->get('content', ''));

    // Create entity
    $chatMessage = new ChatMessage();
    $chatMessage->setContent($content);
    $chatMessage->setSender($user);
    $chatMessage->setVirtualRoom($virtualRoom);

    // Validate using Symfony Assertions (SERVER-SIDE ONLY)
    $errors = $validator->validate($chatMessage);

    if (count($errors) > 0) {
        // Collect error messages
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    } else {
        // Save to database
        $em->persist($chatMessage);
        $em->flush();
        $this->addFlash('success', 'Message envoyé avec succès.');
    }

    return $this->redirectToRoute('community_room_chat', ['id' => $id]);
}
```

---

### Example 2: Create Support Ticket with Multi-Field Validation

```php
// In CommunityController
#[Route('/claim/create', name: 'community_claim_create', methods: ['GET', 'POST'])]
public function createClaim(
    Request $request,
    EntityManagerInterface $em,
    ValidatorInterface $validator
): Response {
    $user = $this->getUser();

    if ($request->isMethod('POST')) {
        // Get form data
        $title = trim($request->request->get('title', ''));
        $description = trim($request->request->get('description', ''));
        $priority = trim($request->request->get('priority', 'medium'));

        // Create entity
        $claim = new Claim();
        $claim->setTitle($title);
        $claim->setDescription($description);
        $claim->setPriority($priority);
        $claim->setCreatedBy($user);
        // Status defaults to 'open'

        // Server-side validation
        $errors = $validator->validate($claim);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', implode(' | ', $errorMessages));
        } else {
            // Persist
            $em->persist($claim);
            $em->flush();

            $this->addFlash('success', "Ticket #{$claim->getId()} créé avec succès!");
            return $this->redirectToRoute('community_claim_list');
        }
    }

    return $this->render('community/create_claim.html.twig');
}
```

---

### Example 3: Send Challenge to Friend with Relationship

```php
// In CommunityController
#[Route('/challenge/send', name: 'community_challenge_send', methods: ['GET', 'POST'])]
public function sendChallenge(
    Request $request,
    EntityManagerInterface $em,
    ValidatorInterface $validator
): Response {
    $user = $this->getUser();

    if ($request->isMethod('POST')) {
        $title = trim($request->request->get('title', ''));
        $description = trim($request->request->get('description', ''));
        $sharedWithId = $request->request->get('shared_with_id');

        // Find recipient
        $sharedWithUser = $em->getRepository(User::class)->find($sharedWithId);

        if (!$sharedWithUser) {
            $this->addFlash('error', 'Utilisateur destinataire introuvable.');
            return $this->redirectToRoute('community_challenge_send');
        }

        // Create task
        $sharedTask = new SharedTask();
        $sharedTask->setTitle($title);
        $sharedTask->setDescription($description);
        $sharedTask->setSharedBy($user);
        $sharedTask->setSharedWith($sharedWithUser);
        // Status defaults to 'pending'

        // Validate
        $errors = $validator->validate($sharedTask);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        } else {
            $em->persist($sharedTask);
            $em->flush();

            $this->addFlash('success', 
                "Défi envoyé à {$sharedWithUser->getEmail()}!");
            return $this->redirectToRoute('community_challenge_inbox');
        }
    }

    // Get all users except self
    $allUsers = $em->getRepository(User::class)->findAll();
    $users = array_filter($allUsers, fn($u) => $u !== $user);

    return $this->render('community/send_challenge.html.twig', [
        'users' => $users,
    ]);
}
```

---

### Example 4: Admin Update Claim with Validation

```php
// In CommunityController
#[Route('/admin/claim/{id}/update-status', 
    name: 'admin_community_claim_update_status', 
    methods: ['POST'])]
public function updateClaimStatus(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    ValidatorInterface $validator
): Response {
    // Check admin permission
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $claim = $em->getRepository(Claim::class)->find($id);

    if (!$claim) {
        $this->addFlash('error', 'Ticket introuvable.');
        return $this->redirectToRoute('admin_community_claims');
    }

    // Get new values
    $status = $request->request->get('status');
    $priority = $request->request->get('priority');
    $adminNotes = trim($request->request->get('admin_notes', ''));

    // Validate choices
    $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
    $validPriorities = ['low', 'medium', 'high', 'critical'];

    if (!in_array($status, $validStatuses)) {
        $this->addFlash('error', 'Statut invalide.');
        return $this->redirectToRoute('admin_community_claims');
    }

    if (!in_array($priority, $validPriorities)) {
        $this->addFlash('error', 'Priorité invalide.');
        return $this->redirectToRoute('admin_community_claims');
    }

    // Update claim
    $claim->setStatus($status);
    $claim->setPriority($priority);
    $claim->setAdminNotes($adminNotes);

    // Set resolved timestamp if status is 'resolved'
    if ($status === 'resolved') {
        $claim->setResolvedAt(new \DateTimeImmutable());
    }

    // Validate updated entity
    $errors = $validator->validate($claim);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    } else {
        $em->flush();
        $this->addFlash('success', 'Ticket mis à jour.');
    }

    return $this->redirectToRoute('admin_community_claims');
}
```

---

### Example 5: Repository Query - Get User Challenges

```php
// In your controller
$sharedTaskRepo = $em->getRepository(SharedTask::class);

// Get pending challenges sent TO the user
$pendingChallenges = $sharedTaskRepo->findBySharedWith(
    $user->getId(), 
    'pending'
);

// Get all challenges ever received
$allReceived = $sharedTaskRepo->findBySharedWith($user->getId());

// Get challenges the user SENT
$sentChallenges = $sharedTaskRepo->findBySharedBy($user->getId());

// Show in template
return $this->render('community/challenge_inbox.html.twig', [
    'tasks' => $pendingChallenges,
    'totalCount' => count($allReceived),
]);
```

---

### Example 6: Repository Query - Admin Dashboard

```php
// In admin controller
$claimRepo = $em->getRepository(Claim::class);

// Get all open/in-progress tickets for dashboard
$openClaims = $claimRepo->findOpenClaims();

// Count for badge
$openCount = $claimRepo->countOpenClaims();

// Filter by status and priority
$criticalOpen = $claimRepo->findByStatusAndPriority('open', 'critical');
$inProgress = $claimRepo->findByStatusAndPriority('in_progress');

return $this->render('admin/community_claims.html.twig', [
    'claims' => $openClaims,
    'openClaimsCount' => $openCount,
    'criticalCount' => count($criticalOpen),
]);
```

---

### Example 7: Relationship Usage - User with All Collections

```php
// Get a user
$user = $em->getRepository(User::class)->find($id);

// Access collections (from User entity relationships)
$userMessages = $user->getChatMessages();        // Count all messages sent
$userClaims = $user->getCreatedClaims();        // All claims created
$assignedClaims = $user->getAssignedClaims();   // All tickets assigned to this admin
$tasksSent = $user->getSharedTasksSent();       // All challenges sent
$tasksReceived = $user->getSharedTasksReceived(); // All challenges received
$roomsCreated = $user->getCreatedRooms();       // Virtual rooms created
$roomsJoined = $user->getJoinedRooms();         // Rooms user participates in

// In template
Total messages: {{ user.chatMessages|length }}
Open tickets assigned to you: {{ user.assignedClaims|length }}
Pending challenges: {{ user.sharedTasksReceived|length }}
```

---

### Example 8: Custom Query in Repository

```php
// In ChatMessageRepository
public function findRecentMessagesFromRoom($roomId, $limit = 10)
{
    return $this->createQueryBuilder('cm')
        ->andWhere('cm.virtualRoom = :room_id')
        ->setParameter('room_id', $roomId)
        ->orderBy('cm.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

// Usage in controller
$recent = $chatMessageRepo->findRecentMessagesFromRoom($roomId, 20);
```

---

## 🐛 Troubleshooting Guide

### Problem: "SQLSTATE[42S02]: Table 'claim' doesn't exist"

**Cause**: Database migration not executed.

**Solution**:
```bash
php bin/console doctrine:migrations:migrate
```

**Verify**:
```bash
php bin/console doctrine:schema:validate
# Output: Schema valid! ✓
```

---

### Problem: "Validation failed: The title should not be blank"

**Cause**: Form submitted with empty title field.

**Solution**: 
The validation is working correctly! Check Twig form:
```html
<input type="text" name="title" required minlength="3" />
```

In controller, you'll catch this:
```php
$errors = $validator->validate($claim);
if (count($errors) > 0) {
    foreach ($errors as $error) {
        $this->addFlash('error', $error->getMessage());
        // Outputs: "Le titre du ticket ne peut pas être vide."
    }
}
```

---

### Problem: "User entity has no relationship with ChatMessage"

**Cause**: `User.php` not updated with relationships.

**Solution**: Verify `User.php` has these imports:
```php
use App\Entity\Community\ChatMessage;
use App\Entity\Community\Claim;
use App\Entity\Community\SharedTask;
```

And these properties:
```php
#[ORM\OneToMany(targetEntity: ChatMessage::class, mappedBy: 'sender', orphanRemoval: true)]
private Collection $chatMessages;
```

---

### Problem: "Access Denied! User is not granted any of the required roles"

**Cause**: Admin route accessed by non-admin.

**Solution**: 
Admin checks are working! In controller:
```php
$this->denyAccessUnlessGranted('ROLE_ADMIN');
```

Check user roles in database:
```sql
SELECT email, roles FROM user WHERE id = 1;
```

---

### Problem: "Method not found on ChatMessage"

**Cause**: Method name typo or entity not reloaded.

**Solution**: 
```bash
# Clear cache
php bin/console cache:clear

# Reload entities (if using IDE)
Ctrl+Shift+P → "Symfony: Cache Clear"
```

Common methods (check casing):
```php
$message->getSender();           // ✓ Correct
$message->get_sender();          // ✗ Wrong (PHP style is camelCase)
$message->sender();              // ✗ Missing "get"
```

---

### Problem: "Too many arguments to function setStatus()"

**Cause**: Wrong entity type or method signature.

**Solution**:
```php
// Status takes ONE argument
$claim->setStatus('open');           // ✓ Correct
$claim->setStatus('open', 'reason'); // ✗ Wrong

// Check method signature in Entity file:
public function setStatus(string $status): self
```

---

### Problem: "Circular dependency detected"

**Cause**: VirtualRoom and ChatMessage relationship misconfigured.

**Solution**: Verify `VirtualRoom.php`:
```php
#[ORM\OneToMany(targetEntity: ChatMessage::class, mappedBy: 'virtualRoom', orphanRemoval: true)]
private Collection $chatMessages;

// And ChatMessage.php:
#[ORM\ManyToOne(targetEntity: VirtualRoom::class, inversedBy: 'chatMessages')]
private ?VirtualRoom $virtualRoom = null;
```

The `mappedBy` and `inversedBy` must match exactly!

---

### Problem: "Messages inserted but appear in reverse order"

**Cause**: Wrong orderBy direction in repository.

**Solution**:
```php
// FOR DISPLAY (newest first):
->orderBy('cm.createdAt', 'DESC')  // ✓ Correct

// FOR PAGINATION (oldest first):
->orderBy('cm.createdAt', 'ASC')   // Sometimes applicable
```

---

### Problem: "Claim stays in 'open' status after form submission"

**Cause**: Validation errors but no error feedback shown.

**Solution**: 
Check if using `$em->flush()` before validation:
```php
// WRONG:
$em->persist($claim);
$em->flush();
$errors = $validator->validate($claim);  // Already saved!

// CORRECT:
$errors = $validator->validate($claim);
if (count($errors) > 0) {
    // Show errors
} else {
    $em->persist($claim);
    $em->flush();
}
```

---

### Problem: "Can't send challenge to myself"

**Cause**: No validation preventing self-challenges.

**Solution**: Add in controller:
```php
if ($sharedWithUser === $user) {
    $this->addFlash('error', 'Vous ne pouvez pas vous envoyer un défi à vous-même.');
    return $this->redirectToRoute('community_challenge_send');
}
```

---

### Problem: "Admin can't see other users to assign claims"

**Cause**: User repository not queried for admin/support users.

**Solution**:
```php
// In admin controller:
$adminUsers = $em->getRepository(User::class)->findBy([
    'roles' => ['ROLE_ADMIN'] // This won't work (roles is array)
]);

// Instead:
$allUsers = $em->getRepository(User::class)->findAll();
$adminUsers = array_filter($allUsers, fn($u) => 
    in_array('ROLE_ADMIN', $u->getRoles())
);
```

---

### Problem: "Timestamps are null in database"

**Cause**: Lifecycle callback not triggered.

**Solution**: Verify entity has:
```php
#[ORM\PrePersist]
public function onPrePersist(): void
{
    $this->createdAt = new \DateTimeImmutable();
}

#[ORM\PreUpdate]
public function onPreUpdate(): void
{
    $this->updatedAt = new \DateTimeImmutable();
}
```

---

### Problem: "Entity Manager doesn't persist changes"

**Cause**: Missing `$em->flush()` or `$em->persist()`.

**Solution**:
```php
$chatMessage = new ChatMessage();
$chatMessage->setContent('Hello');

// STEP 1: Tell Doctrine about new entity
$em->persist($chatMessage);

// STEP 2: Execute INSERT query
$em->flush();

// Without these, database stays unchanged!
```

---

## ✅ Pre-Deployment Checklist

```bash
# 1. Clear cache
php bin/console cache:clear --env=prod

# 2. Validate schema
php bin/console doctrine:schema:validate

# 3. Run migrations
php bin/console doctrine:migrations:migrate

# 4. Verify routes
php bin/console debug:router | grep community

# 5. Run tests
php bin/phpunit

# 6. Check entities
php bin/console debug:mapping

# 7. Verify database
mysql -u root -p mindforge -e "SHOW TABLES LIKE 'chat_%';"
```

---

## 📊 Performance Tips

### For Large Chat Rooms
```php
// SLOW: Load all messages
$all = $em->getRepository(ChatMessage::class)
    ->findBy(['virtualRoom' => $roomId]);

// FAST: Paginated with limit
$messages = $repo->findByVirtualRoomPaginated($roomId, 50, $offset);
```

### Index Queries

The database has indexes on:
- `chat_message.virtual_room_id` (for room queries)
- `shared_task.shared_with_id` (for inbox queries)
- `shared_task.shared_by_id` (for outbox queries)
- `claim.created_by_id` (for user ticket queries)

These are automatically created by the migration.

---

## 🔒 Security Hardening

```php
// ALWAYS verify authorization before admin operations
if (!$this->isGranted('ROLE_ADMIN')) {
    throw $this->createAccessDeniedException('Admin access required');
}

// ALWAYS check ownership for user operations
if ($claim->getCreatedBy() !== $this->getUser()) {
    throw $this->createAccessDeniedException('You do not own this claim');
}

// ALWAYS validate input on server-side
$errors = $validator->validate($entity);
if (count($errors) > 0) {
    // Handle errors...
}
```

---

**Still stuck?** Check `COMMUNITY_MODULE_GUIDE.md` for detailed documentation or create an issue with steps to reproduce.
