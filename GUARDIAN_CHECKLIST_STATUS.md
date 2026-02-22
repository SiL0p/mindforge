# Guardian Checklist Status

Date: 2026-02-22

## 1) Intégration + personnalisation de bundle(s) externe(s)
Status: ✅ Done

- Bundle externe installé: `knplabs/knp-paginator-bundle`
- Personnalisation Guardian: pagination + filtres type/source sur admin AI insights
- Bundle UX ajouté: `symfony/ux-chartjs` pour visualisation admin des insights IA
- Fichiers: `config/packages/knp_paginator.yaml`, `src/Controller/Guardian/Admin/GuardianAdminController.php`, `src/Repository/Guardian/AiInsightRepository.php`, `templates/admin/guardian/ai_insight_index.html.twig`, `assets/admin_app.js`, `templates/admin/dashboard.html.twig`

## 2) Intégration des APIs
Status: ✅ Done (interne + externe)

- APIs internes Guardian: Focus Timer (`overview/recommendation/log/tips/daily-plan/weekly-review`)
- API tierce externe intégrée: Open Library (`/guardian/library/api/external-suggestions`)
- API IA provider-based: OpenAI / Gemini / Claude (configurable via env)
- Fichiers: `src/Controller/Guardian/GuardianController.php`, `src/Service/Guardian/ExternalLearningResourceService.php`, `src/Service/Guardian/GuardianAiAssistant.php`, `templates/user/guardian/library.html.twig`, `config/services.yaml`, `.env`

## 3) Développement des fonctionnalités métiers avancées
Status: ✅ Done

- Rooms: join/leave/capacité/état
- Library: upload/download/suppression/filtres + marquage source (`AI`/`Manual`)
- Focus: sessions, règles anti-duplication, contrôles métier renforcés, gamification
- Génération de ressources: création réelle de PDF (Dompdf) avec nom/extension cohérents
- Admin: resource/rooms + historique insights IA
- Fichiers: `src/Entity/Guardian/Resource.php`, `src/Form/Guardian/AdminResourceEditType.php`, `migrations/Version20260222093000.php`, `templates/user/guardian/resource_upload.html.twig`, `templates/user/guardian/library.html.twig`

## 4) Intégration de l'IA
Status: ✅ Done

- Recommandation IA de durée focus + tips + daily plan + weekly review
- Génération IA de ressources d'apprentissage (fallback local intelligent si clé absente)
- Support multi-provider IA (`openai`, `gemini`, `claude`) avec sélection runtime
- Historisation des réponses IA: table `guardian_ai_insight`
- Fichiers: `src/Service/Guardian/GuardianAiAssistant.php`, `src/Controller/Guardian/GuardianController.php`, `src/Entity/Guardian/AiInsight.php`, `src/Repository/Guardian/AiInsightRepository.php`, `migrations/Version20260221103000.php`

## Tests Guardian

- `tests/Guardian/FocusTimerRoutesTest.php`
- `tests/Guardian/FocusTimerBusinessRulesTest.php`
- `tests/Guardian/LibraryExternalApiRoutesTest.php`
- `tests/Guardian/AdminAiInsightPageTest.php`
- `tests/Guardian/AiIntegrationRoutesTest.php`
