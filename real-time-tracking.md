# Real-Time Tracking & Funnel Visualization

## Goal
Implement a real-time tracking system to monitor node-by-node progress for both anonymous and registered users, and visualize this data in the Admin Dashboard organized by access code.

## Tasks
- [x] **Task 0: Analysis & Planning**  
  Define requirements and database modifications for anonymous tracking.

- [ ] **Task 1: Database Migration for Anonymous Tracking**  
  Modify `prospect_access_logs` and `prospect_node_progress` to make `prospect_id` nullable and add `session_id` (string). Add `access_code_id` to `prospect_node_progress`.  
  → Verify: `php artisan migrate` success and tables updated.

- [ ] **Task 2: Backend: Tracking API Implementation**  
  Update `ProspectProgressController` with a `trackNode` method. Update `PublicAccessController` to log a visit when a code is validated.  
  → Verify: POST `/v1/public/prospect/track-node` returns 200 with valid session/code.

- [ ] **Task 3: Frontend: Real-Time Tracking Logic**  
  In `CourseView.vue` and `CoursesLanding.vue`, generate/retrieve a `session_id`. Call the tracking API on node load and navigation.  
  → Verify: Browser console shows tracking requests on each node change.

- [ ] **Task 4: Backend: Admin Stats Aggregation**  
  Create a new `AdminStatsController` with an endpoint to get visits and node views aggregated by `access_code`.  
  → Verify: GET `/v1/admin/stats/by-code` returns structured data with counts per code/node.

- [ ] **Task 5: Frontend: Admin Funnel Visualization**  
  Update `DashboardView.vue` (or create a sub-view) to display tables showing visits, progress, and drop-off per access code.  
  → Verify: Admin dashboard displays real data tables for each code.

## Done When
- [ ] Anonymous visits are recorded per code even without registration.
- [ ] Every "Next" click (node view) is logged in the database.
- [ ] Admin dashboard shows a clear table for each code with visits and progress per node.
- [ ] Registered prospects are correctly linked to their previous anonymous session data.

## Notes
- `session_id` will be a UUID stored in `localStorage`.
- When a prospect registers (`sync`), we will associate existing logs with that `session_id` to the new `prospect_id`.
