---
title: Patients' data
order: 4
---

# The patient's space and their data

`/espace` (`ROLE_USER`): the next appointments (join the video room, add to a calendar, cancel), the
documents not read yet, the home care asked, the care team. `/espace/profil`: who they are for the
practice, their "médecin traitant", and their say on sharing within the team. `/espace/proches`:
their `Dependent`s - who they book for (the booking then offers them, through omnibase/office's
`BeneficiaryProviderInterface`).

A `PatientProfile` is not a medical record: nothing clinical is kept in it.

## Rights (GDPR art. 15, 17, 20)

`Base\Health\Service\PatientData`, from `/espace/donnees`:

- `export($user)`: one array - account, profile, dependants, linked identities, appointments with
  their reasons decrypted, home care requests, the list of documents, who accessed them;
- `zip($user)`: `data.json` and each available document, decrypted;
- `erase($user)`: the profile emptied, the dependants removed, the appointments to come cancelled,
  the pending requests cancelled, the documents they deposited destroyed, those sent to them
  withdrawn. What must be kept stays, bare, until `health:purge`.

## Retention

`health:purge` removes, after `health.retention` months: past appointments, decided home care
requests, the access log; documents expired or withdrawn, their files destroyed; contact requests
(omnibase/office's `contact.retention_months`); closed video rooms' handshakes. The durations are the
practice's to set with its DPO; the defaults are placeholders, not legal advice.
