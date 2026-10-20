# Speed-to-Lead

A local business connects in a few minutes. After that, a new lead is answered immediately, qualified, and booked. The business does not have to reply by hand.

This document is the frozen first version. Do not add channels, CRMs, or a knowledge platform until this one flow feels finished.

## Stack

- Laravel
- Laravel AI (`laravel/ai`) as the only model layer. One provider: OpenRouter, using the free model router so a reply does not need paid credits.
- MySQL for the app database
- Inertia + React for the dashboard, using the starter kit’s existing components, icons, and form controls
- Database queue driver until reply volume needs Redis. The first reply still runs in the request so the customer is not waiting on a worker.

The starter kit in this repository is Inertia with React, not Vue. The dashboard stays on that kit so auth, layout, and the component library stay intact.

## The only flow that must work

```text
Website lead
    → Laravel receives it
    → one lead record
    → Laravel AI (or the local fallback when no API key is set)
    → reply to the customer
    → continue the conversation
    → qualified
    → check the business calendar
    → book the appointment
    → the business sees it on the dashboard
```

Later inputs (WhatsApp, SMS, Facebook, phone, Google, Yelp) must become the same lead. They are not part of this version.

## Onboarding

Do not ask for a long questionnaire.

```text
How should we learn about your business?
    Website URL  |  Google Business (later)  |  Enter it yourself
        → extract a profile
        → review and confirm
        → connect the website form
```

The profile stores facts with a source and a confidence, for example “Roof repair / website / high” or “Mon–Fri 8:00–6:00 / owner / high”. No vector database.

What we keep:

- Name, phone, website, description, city, region
- Services
- Hours used for booking
- FAQs, pricing, and other facts when we actually have them
- Booking length and notice time

Google Business Profile is shown as a later choice. This version does not call Google.

## Lead

Every source becomes one shape:

```text
name, email, phone, source, message, service_requested, location, status, conversation, created_at
```

Statuses the dashboard shows:

- Waiting for response
- Qualified
- Appointment booked

## Calendar

The business hours are the calendar. Openings are the next free slots inside those hours. Booking writes an appointment and notifies the owner by email.

## Screens

1. Marketing home
2. Tell us about your business
3. Review and confirm
4. New leads inbox
5. One lead, with the conversation and the appointment
6. Business profile
7. Website connection: public form link, webhook, and a sample lead
8. Public form and public conversation for the customer

## Out of scope

WhatsApp, SMS, Facebook, phone, Yelp, other CRMs, Google Calendar sync, voice, and any system that “learns the whole business” beyond the saved profile.
