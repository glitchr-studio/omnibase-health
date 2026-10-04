---
title: Home care
order: 3
---

# Care at home

```php
$request = $homeCare->submit($patient, [
    'street' => '8 rue du Moulin', 'postalCode' => '67998', 'city' => 'Aubach', 'phone' => '…',
    'careType' => 'dressing',            // injection, dressing, blood_test, hygiene, infusion, monitoring, medication, other
    'frequency' => 'daily',              // once, daily, every_other_day, weekly
    'startDate' => $from, 'endDate' => $until, 'preferredTime' => 'morning', 'notes' => '…',
], $dependent, $prescription);
```

1. **The address is checked first** against omnibase/office's `ServiceArea`s (postcodes, towns, or a
   radius). Outside all of them: `HomeCareException('home_care.error.outside_area')`, and nothing is
   kept - not the request, not the prescription.
2. Inside: a `HomeCareRequest`, `pending`. The note is stored encrypted; the prescription, if any, is
   a document of the vault.
3. The practice answers in `/admin/agenda/soins-a-domicile`: who goes, the day and hour of the first
   visit. `HomeCare::accept()` books the visits as a series (`Booker::series()`): one at the
   request's frequency from the first visit to the end date, counted in calendar days, all or none.
   Or `refuse()`.

The members offered are those who have a home-visit appointment type (`channel: home`).
