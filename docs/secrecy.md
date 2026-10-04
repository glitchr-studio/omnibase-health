---
title: Medical secrecy
order: 2
---

# Medical secrecy, as the tool applies it

## Documents

omnibase/office's vault lets the recipient and the sender read a document, and nobody else unless a
regime says so. `Base\Health\Security\CareTeamAudience` is that regime:

| Who | Sees that it exists (title) | Reads it |
|---|---|---|
| The patient | yes | yes |
| Its author (whoever sent it) | yes | yes |
| A practitioner who follows the patient | yes | yes - **unless the patient objected** |
| The secretariat | yes | no (a document not marked confidential - an administrative letter - yes) |
| Another practitioner, the coordination, anyone else | no | no |

"Follows the patient" is `Base\Health\Service\CareTeam`: the members the patient has, or had, an
appointment with (not a cancelled one), and the practice's physician they declared as their "médecin
traitant". The patient's objection is `PatientProfile::sharingOpposition`, set in their own profile.

Every view, download, deposit, withdrawal - and every refusal - is in the access log, which the
patient reads in `/espace/donnees` and the coordination in the back office.

## E-mails

None holds health data: a booking's e-mail says when and with whom, not why nor what for; "a document
awaits you" has neither the document nor its title; "a message awaits you" has neither its text nor its
subject. All of it is read signed in.

## At rest

Documents, their titles and file names, appointments' reasons, home care notes: encrypted by
omnibase/office's `Cipher`. Messages and their subjects: by omnibase/mailbox (`encrypt: true`). Both
fail closed without their key.

## The staff's second factor

`health.staff_two_factor`: a staff account (`ROLE_STAFF`) with no second factor is redirected to
`/settings` from every page until it has one. Patients choose.

## The video

End to end between the two browsers, the relay one's own. It is **not** listed by the ANS: no
reimbursable teleconsultation through it - the compliance widget and `/teleconsultation` say so.

## Hosting

Health data must be hosted by an HDS-certified host. The bundle cannot check that; it asks for the
host's name (Settings), prints it in the legal notice, and marks it missing until it is given.
