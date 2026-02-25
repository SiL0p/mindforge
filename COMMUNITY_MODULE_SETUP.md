# Configuration du module Community avec Shared Tasks (Challenges)

## Variables d'environnement requises

Ajoutez ces variables dans votre fichier `.env.local` :

```env
# OpenAI API - Pour les suggestions et améliorations IA
OPENAI_API_KEY=sk-your-api-key-here

# Mailer - Pour les notifications par email
MAILER_DSN=sendgrid+api://your-sendgrid-key@default
# Ou pour développement (console output) :
MAILER_DSN=null://null

# Email d'expédition
MAILER_FROM=noreply@mindforge.com
```

## Configuration requise

### 1. OpenAI API Key
- Allez sur https://platform.openai.com/api-keys
- Créez une nouvelle clé API
- Ajoutez-la dans `.env.local` : `OPENAI_API_KEY=sk-...`

### 2. SendGrid (Optionnel en dev, requis en prod)
- Inscrivez-vous sur SendGrid : https://sendgrid.com
- Créez une clé API
- Configurez `MAILER_DSN` avec votre clé

### 3. Dossier d'uploads
Le dossier `public/uploads/challenges/` sera créé automatiquement

## Fonctionnalités implémentées

### 1. VichUploaderBundle 📦
- Upload de fichiers/images aux défis
- Formats : PDF, JPG, PNG, DOC, DOCX
- Taille max : 5MB
- Chemin stockage : `public/uploads/challenges/`

### 2. SendGrid API 🌐
Notifications automatiques :
- ✉️ Quand défi reçu
- ✉️ Quand défi accepté
- ✉️ Quand défi rejeté
- ✉️ Quand défi complété
- ⏰ Rappel après 3 jours (pas de réponse)

### 3. Catégories de Défis 🎯
- **tech_skills** : Compétences techniques
- **soft_skills** : Compétences soft
- **physical** : Défis physiques
- **creative** : Défis créatifs

### 4. IA - ChatGPT 🤖
Générations automatiques :
- Suggestions de défis par catégorie
- Amélioration des descriptions
- Recommandation de difficulté

## Routes disponibles

### Frontend
```
GET  /community/challenge/send              - Formulaire d'envoi
GET  /community/challenge/inbox             - Défis reçus
GET  /community/challenge/outbox            - Défis envoyés
GET  /community/challenges/category/{cat}   - Défis par catégorie
POST /community/challenge/{id}/respond      - Accepter/Rejeter
```

### API IA
```
GET /community/challenge/ai-suggestions/{category}  - Suggestions JSON
GET /community/challenge/ai-improve?text={text}     - Amélioration JSON
```

## Migration Doctrine

Pour mettre à jour la base de données :

```bash
php bin/console doctrine:migrations:migrate
```

Ou pour vérifier l'état :

```bash
php bin/console doctrine:migrations:status
```

## Modèles de données

### SharedTask
```php
- id: int (PrimaryKey)
- title: string (255)
- description: text (nullable)
- category: string (tech_skills|soft_skills|physical|creative)
- difficulty: string (easy|medium|hard)
- attachment: string (255, nullable - nom du fichier)
- status: string (pending|accepted|rejected|completed)
- sharedBy: User (expéditeur)
- sharedWith: User (destinataire)
- createdAt: datetime_immutable
- respondedAt: datetime_immutable (nullable)
- updatedAt: datetime_immutable (nullable)
```

## Dépannage

### Erreur : "Cannot autowire service ChallengeAIService"
✅ **Résolu** - La clé API est maintenant bindée dans `config/services.yaml`

### OPENAI_API_KEY non configurée
L'application utilisera des suggestions par défaut (fallback)

### Emails ne sont pas envoyés
- Vérifiez `MAILER_DSN` dans `.env.local`
- En dev, utilisez `MAILER_DSN=null://null` pour voir les emails en console

## Exemple de scénario complet

1. **User A** envoie un défi à **User B**
   ```
   POST /community/challenge/send
   - Titre: "Apprendre Docker"
   - Description: "Maîtriser les conteneurs"
   - Catégorie: tech_skills
   - Fichier: tutoriel.pdf (optionnel)
   ```

2. **IA** suggère :
   - Difficulté: "hard"
   - Amélioration description (si trop vague)

3. **SendGrid** envoie email à User B

4. **User B** reçoit dans inbox et répond :
   - POST /community/challenge/{id}/respond
   - Réponse: "accepted" ou "rejected"

5. **SendGrid** notifie User A de la réponse

6. Si pas réponse après 3 jours → Rappel envoyé

## Support

Pour toute question sur l'implémentation, consultez :
- Service: `src/Service/ChallengeAIService.php`
- Service: `src/Service/CommunityNotificationService.php`
- Controller: `src/Controller/Community/CommunityController.php`
- Entité: `src/Entity/Community/SharedTask.php`
