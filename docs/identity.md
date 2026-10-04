---
title: Identity
order: 5
---

# FranceConnect and Pro Santé Connect

The bundle provides one thing: `Base\Health\Entity\IdentityLink` - a provider, its `sub`, the account
- with a unique index on (provider, sub), and its repository (`findOneBySub()`).

**An identity is attached by its `sub`, never by an e-mail address.** An address proves nothing about
who someone is; attaching a health account to whoever shows the same address would hand it over.

The protocol itself is not the bundle's: an application uses `drenso/symfony-oidc-bundle` (two
clients) and writes its linker. The rule it should follow:

- an identity already linked signs in its account;
- someone already signed in who comes back from the provider links it to their account;
- Pro Santé Connect, nobody signed in: the practitioner of the team whose RPPS number it is
  (`PractitionerRepository::findOneByRpps()` - the national identifier is "8" + RPPS); nobody of the
  team: refused;
- FranceConnect, never seen: a first visit - sign in to an existing account to link it, or open a
  patient's account from the identity.

FranceConnect v2 answers its userinfo as a signed JWT, which drenso's client does not read
(it `json_decode()`s the body): verify it against the provider's JWKS (`lcobucci/jwt`).
