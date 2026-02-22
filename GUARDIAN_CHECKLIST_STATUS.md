# Guardian Checklist Status

Date: 2026-02-21

## 1) Intégration + personnalisation de bundle(s) externe(s)
Status: ✅ Done

- Bundle externe installé: `knplabs/knp-paginator-bundle`
- Personnalisation Guardian: pagination + filtres type/source sur admin AI insights
- Fichiers: `config/packages/knp_paginator.yaml`, `src/Controller/Guardian/Admin/GuardianAdminController.php`, `src/Repository/Guardian/AiInsightRepository.php`, `templates/admin/guardian/ai_insight_index.html.twig`

## 2) Intégration des APIs
Status: ✅ Done (interne + externe)

- APIs internes Guardian: Focus Timer (overview/recommendation/log/tips/daily plan)
- API tierce externe intégrée: Open Library (`/guardian/library/api/external-suggestions`)
- Fichiers: `src/Controller/Guardian/GuardianController.php`, `src/Service/Guardian/ExternalLearningResourceService.php`, `templates/user/guardian/library.html.twig`

## 3) Développement des fonctionnalités métiers avancées
Status: ✅ Done

- Rooms: join/leave/capacité/état
- Library: upload/download/suppression/filtres
- Focus: sessions, anti-duplication, gamification
- Admin: resource/rooms + AI insights history

## 4) Intégration de l'IA
Status: ✅ Done

- IA OpenAI intégrée dans Guardian Focus Timer
- Durée recommandée IA + tips + daily plan
- Historisation des réponses IA: table `guardian_ai_insight`
- Fichiers: `src/Service/Guardian/GuardianAiAssistant.php`, `src/Controller/Guardian/GuardianController.php`, `src/Entity/Guardian/AiInsight.php`, `migrations/Version20260221103000.php`

## Tests Guardian

- `tests/Guardian/FocusTimerRoutesTest.php`
- `tests/Guardian/LibraryExternalApiRoutesTest.php`
- `tests/Guardian/AdminAiInsightPageTest.php`
- `tests/Guardian/AiIntegrationRoutesTest.php`
