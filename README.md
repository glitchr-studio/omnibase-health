# omnibase/health

The health regime on [omnibase/office](https://github.com/glitchr-studio/omnibase-office): the site of a
practice of care - a multi-professional health centre, a nursing practice, a physiotherapist, a midwife.

- `Practice` (its kind, FINESS, what to do out of hours) and `Practitioner` (profession, agreement
  with the health insurance, Carte Vitale, MSSanté address), read from the Annuaire Santé by RPPS
  number (`glitchr/omnistate` + `omnistate/annuaire-sante`);
- patients: `PatientProfile`, their `Dependent`s, the patient's space (`/espace`), their data to take
  away or to have erased;
- care at home: a request checked against the service areas, accepted as a series of visits;
- fees displayed (`/tarifs`), the emergency banner, `/urgences`, `/teleconsultation`;
- **medical secrecy** in who may read a document: the patient, its author, the care team unless the
  patient objected; the secretariat files and sends, it does not read;
- a second factor mandatory for the staff; compliance checks (keys, HDS host, fees, video not
  ANS-listed);
- `IdentityLink`: whose account a FranceConnect or Pro Santé Connect identity is - by `sub`.

No e-mail ever holds health data, not even a document's title or an appointment's reason.

It is not a medical record, a billing tool (no SESAM-Vitale), nor a teleconsultation solution listed
by the ANS.

```sh
composer require omnibase/health
```

Documentation: [docs/](docs/index.md). License: LGPL-3.0-or-later.
