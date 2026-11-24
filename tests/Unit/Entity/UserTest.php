<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $user = new User();

        self::assertSame([User::USER_ROLE_USER], $user->getRoles());
        self::assertTrue($user->isActive());
        self::assertSame('', $user->getName());
    }

    public function testUserIdentifierIsTheUsername(): void
    {
        $user = (new User())->setUsername('ada');

        self::assertSame('ada', $user->getUserIdentifier());
    }

    public function testEveryUserHasAtLeastRoleUserAndRolesAreUnique(): void
    {
        $user = (new User())->setRoles([User::USER_ROLE_ADMIN, User::USER_ROLE_USER]);

        self::assertSame(
            [User::USER_ROLE_ADMIN, User::USER_ROLE_USER],
            $user->getRoles(),
        );

        $user->setRoles([]);

        self::assertSame([User::USER_ROLE_USER], $user->getRoles());
    }
}
