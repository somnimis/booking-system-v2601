# Facility & Availability Calendar: Overview

This document explains the logic and UI behavior of the Facility Hierarchy and Availability Calendar. It is intended for project managers, QA testers, and future developers to understand how the system handles buildings, rooms, and event rendering.

## 1. Facility Hierarchy & Booking Rules

Our facilities are structured in a parent-child relationship to represent the physical campus layout.

### The Two Types of Facilities
1. **Standalone Venues:** Facilities that do not contain other rooms (e.g., a standalone Gymnasium or a standalone Auditorium). **These are bookable.**
2. **Container Buildings:** Facilities that contain individual rooms (e.g., a College Building). **These are NOT bookable.** They only act as containers to group the rooms inside them.
3. **Rooms:** The child facilities inside a Container Building. **These are bookable.**

### The "No Booking Containers" Rule
- The system prevents users from booking an entire Container Building. 
- In the "Venues" tab, Container Buildings are completely hidden from the filter dropdown. Only bookable standalone venues and individual rooms appear in relevant areas.

---

## 2. The Calendar Matrix Tabs

The calendar uses two tabs: **Venues** and **Campus Rooms**. 

### Venues Tab
- **What it shows:** Only standalone bookable facilities.
- **Filter Dropdown:** Lists only standalone venues.
- **Pagination:** Shows a maximum of 10 venues per page.

### Campus Rooms Tab
- **What it shows:** A grouped list of rooms. Rooms are grouped under their parent Container Building.
- **Filter Dropdown:** Lists only Container Buildings (so you can filter to see only the rooms inside a specific building).
- **Pagination:** Shows a maximum of 10 **rooms** per page.
  - *Note:* If a building has 15 rooms, the first 10 will show on Page 1, and the remaining 5 will show on Page 2. The building header will repeat on Page 2 to maintain visual grouping.
- **Single Room Selection:** If a user selects a specific room from the dropdown, pagination is bypassed, and only that single room is shown.

---

## 3. Event Display & Status Logic

Events on the calendar come from two sources, and the colors represent their status.

### Status Colors
- **Green (Available):** No bookings or events exist in this time slot.
- **Yellow (Pending):** A requisition exists but is awaiting approval or payment (`Pending Approval`, `Awaiting Payment`, `Verifying Payment`).
- **Red (Booked):** A requisition has been fully approved and confirmed (`Reserved`).
- **Purple (System Event):** A non-requisition event (e.g., Holiday, School Event, or a blocked hall booking).

### Status Exclusions
- Requisitions with the status `Completed`, `Rejected`, or `Cancelled` are **never** displayed on the calendar.
- **Date Accuracy:** The calendar relies on overlapping logic. If an event spans from 10:30 AM to 12:30 PM, the 10:30 slot and 12:00 slot will show as booked.

### System Events (Calendar Events)
- **Hall Bookings:** Only appear on the specific rows (venues) they are assigned to.
- **School Events & Holidays:** Appear globally across *all* rows, because they affect the entire campus.

---

## 4. Hover Cards & Modals

Clicking or hovering over an event shows details. 

- **Hover Card (The small popup):** 
  - Shows the Title (Bold).
  - Shows the Status chip (e.g., "Reserved", "holiday").
  - Shows the Event Details (or description).
  - Shows the formatted date and time range (e.g., "Oct 26, 2026 · 8:00am – 10:00am"). 
- **On the Admin Page:** Clicking a Requisition event jumps directly to that requisition's review page. Clicking a System Event opens the modal.
- **On the Public Page:** Clicking any event opens a detailed modal.

---

## 5. Global Search Behavior

The search bar operates independently of the currently displayed date and pagination.

- **How it works:** Typing in the search bar replaces the calendar matrix with a flat list of search results.
- **What it searches:** 
  - Requisition Titles and Details.
  - Calendar Event Names and Descriptions.
  - **Facility Names** (e.g., searching "Gymnasium" will find all events in the Gymnasium).
- **Result Click:** Clicking a search result will automatically change the calendar's date to the event's start date, close the search results, and render the normal calendar matrix on that date. Pressing `Esc` or "Clear" exits the search mode.