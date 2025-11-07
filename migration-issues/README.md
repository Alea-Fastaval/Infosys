# Migration Issues

This directory contains individual issue templates for migrating the Infosys PHP application to .NET + Angular.

## Issue Organization

Issues are numbered and named by feature area. Each issue is a markdown file that can be:
1. Copied to the new monorepo as a GitHub issue
2. Used as a planning document
3. Converted to work items in project management tools

## Using These Issues

### Create GitHub Issues

For each file:
1. Copy the content
2. Create a new issue in the target repository
3. Apply the suggested labels
4. Assign to appropriate team members
5. Add to project board

### Estimation

Estimates are provided in story points:
- 1-3 points: Small task, 1-3 days
- 5-8 points: Medium task, 1-2 weeks
- 8-13 points: Large task, 2-3 weeks
- 13+ points: Should be broken down

### Priority Levels

- **Critical**: Must be done first, blocks other work
- **High**: Core functionality, high business value
- **Medium**: Important but not blocking
- **Low**: Nice to have, can be deferred

## Issue List

1. **001-participant-api.md** - Participant/Attendee API (HIGH)
2. **002-authentication.md** - Authentication & Authorization (CRITICAL)
3. **003-activity-api.md** - Activity & Schedule Management API (HIGH)
4. **004-database-setup.md** - Database Schema & EF Setup (CRITICAL)
5. **005-payment-integration.md** - Payment Integration (HIGH)

Additional issues should be created for:
- GDS shift management API
- Shop/economy API
- Food management API
- Entrance tracking API
- Wear/merchandise API
- Rooms management API
- Photo management API
- Boardgame library API
- Loans system API
- Newsletter system API
- Ticketing system API
- SMS integration
- Email system
- Translation system
- Admin tools API
- Signup module API
- Frontend Angular components (per feature area)
- Barcode generation service
- PDF generation service
- Excel import/export service
- Reporting system
- Data migration scripts
- Testing infrastructure
- CI/CD pipeline
- Deployment strategy

## Dependencies

Many issues depend on:
- #002 (Authentication) - Almost everything needs auth
- #004 (Database Setup) - Required for all data operations
- #001 (Participant API) - Core feature that others relate to

Review dependency chains before starting work.

## Suggested Work Order

### Phase 1: Foundation (Sprints 1-2)
1. Database setup (#004)
2. Authentication (#002)

### Phase 2: Core Features (Sprints 3-6)
3. Participant API (#001)
4. Activity API (#003)
5. Payment integration (#005)

### Phase 3: Secondary Features (Sprints 7-10)
6. GDS system
7. Shop/economy
8. Food management
9. Resource management (rooms, wear, entrance)

### Phase 4: Supporting Features (Sprints 11-14)
10. Boardgames library
11. Loans system
12. Ticketing
13. Newsletter

### Phase 5: Admin & Tools (Sprints 15-16)
14. Admin interfaces
15. Reporting
16. Tools and utilities

### Phase 6: Angular Frontend (Sprints 17-24)
17. Core components and routing
18. Participant management UI
19. Activity management UI
20. Remaining feature UIs

## Notes

- Estimates are rough and should be refined during sprint planning
- Some issues may need to be split into smaller tasks
- Technical debt and refactoring time should be accounted for
- Testing time is included in estimates
- Documentation time is included in estimates

