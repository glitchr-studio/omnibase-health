<?php

namespace Base\Health\Tests\Security;

use App\Entity\User;
use Base\Health\Security\CareTeamAudience;
use Base\Health\Service\CareTeam;
use Base\Office\Entity\Share\Document;
use Base\Office\Share\AudienceResolverInterface as Audience;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * Medical secrecy as the tool applies it, beyond the patient and the
 * author (who are the voter's own business): who of the staff sees that a
 * document exists, who reads it.
 */
final class CareTeamAudienceTest extends TestCase
{
    private User $patient;

    protected function setUp(): void
    {
        $this->patient = $this->user([]);
    }

    public function testAPractitionerWhoFollowsThePatientReads(): void
    {
        $audience = $this->audience(follows: true);
        $practitioner = $this->user(['ROLE_PRACTITIONER']);

        self::assertTrue($audience->decide(Audience::READ, $this->document(), $practitioner));
        self::assertTrue($audience->decide(Audience::VIEW, $this->document(), $practitioner));
        self::assertNull($audience->decide(Audience::REVOKE, $this->document(), $practitioner), 'only its author withdraws a document');
    }

    public function testOneWhoDoesNotFollowThemDoesNot(): void
    {
        $audience = $this->audience(follows: false);

        self::assertNull($audience->decide(Audience::READ, $this->document(), $this->user(['ROLE_PRACTITIONER'])));
        self::assertNull($audience->decide(Audience::VIEW, $this->document(), $this->user(['ROLE_PRACTITIONER'])));
    }

    public function testThePatientsOppositionClosesTheTeamsAccess(): void
    {
        $audience = $this->audience(follows: true, opposition: true);

        self::assertNull($audience->decide(Audience::READ, $this->document(), $this->user(['ROLE_PRACTITIONER'])));
        self::assertNull($audience->decide(Audience::VIEW, $this->document(), $this->user(['ROLE_PRACTITIONER'])));
    }

    public function testTheSecretariatSeesTheTitleNotTheContent(): void
    {
        $audience = $this->audience(follows: false);
        $secretary = $this->user(['ROLE_SECRETARY']);

        self::assertTrue($audience->decide(Audience::VIEW, $this->document(), $secretary));
        self::assertNull($audience->decide(Audience::READ, $this->document(), $secretary));
        self::assertTrue($audience->decide(Audience::READ, $this->document(confidential: false), $secretary), 'an administrative letter is read by the staff');
    }

    public function testNobodyOutsideTheStaff(): void
    {
        $audience = $this->audience(follows: true);

        foreach ([[], ['ROLE_USER'], ['ROLE_SUPERADMIN_OF_SOMETHING_ELSE']] as $roles) {
            self::assertNull($audience->decide(Audience::READ, $this->document(confidential: false), $this->user($roles)));
            self::assertNull($audience->decide(Audience::VIEW, $this->document(), $this->user($roles)));
        }
    }

    private function audience(bool $follows, bool $opposition = false): CareTeamAudience
    {
        $team = $this->createStub(CareTeam::class);
        $team->method('follows')->willReturn($follows);
        $team->method('hasOpposition')->willReturn($opposition);

        return new CareTeamAudience($team, new RoleHierarchy(['ROLE_PRACTITIONER' => ['ROLE_STAFF'], 'ROLE_SECRETARY' => ['ROLE_STAFF'], 'ROLE_STAFF' => ['ROLE_USER']]));
    }

    private function document(bool $confidential = true): Document
    {
        return (new Document($this->patient))->setConfidential($confidential);
    }

    /** @param list<string> $roles */
    private function user(array $roles): User
    {
        $user = $this->createStub(User::class);
        $user->method('getRoles')->willReturn($roles);

        return $user;
    }
}
