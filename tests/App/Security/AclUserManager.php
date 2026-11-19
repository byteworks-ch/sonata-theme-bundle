<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Security;

use Sonata\AdminBundle\Util\AdminAclUserManagerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * The users the form of an object's access control list offers (environment "acl").
 */
final class AclUserManager implements AdminAclUserManagerInterface
{
    public function findUsers(): iterable
    {
        return [new InMemoryUser('admin', null, ['ROLE_SUPER_ADMIN']), new InMemoryUser('editor', null, ['ROLE_EDITOR'])];
    }
}
