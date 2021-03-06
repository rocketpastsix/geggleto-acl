<?php
/**
 * Created by PhpStorm.
 * User: Glenn
 * Date: 2016-01-04
 * Time: 3:35 PM
 */

namespace Geggleto\Acl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

class AclRepository
{
    /**
     * @var \Zend\Permissions\Acl\Acl
     */
    protected $acl;

    /**
     * @var array
     */
    protected $role;

    /**
     * AclRepository constructor.
     *
     * the user_id injected should be a "role name"
     * Each Role would actually be a user.
     * Then each User can have 1 or more different roles
     * which then correspond to one or more different resources
     *
     * $aclList = [
     * "resources" => [list of resources names]
     * "roles" => [list of role names]
     * "assignments" => [
     *      "allow" => [
     *          "rolename" => [list of resources]
     *      ]
     *      "deny" => [
     *          "rolename" => [list of resources]
     *      ]
     * ]
     *
     *
     * @param string $role This is the current Role(s) you are testing for
     * @param array  $aclList
     */
    public function __construct(array $role, array $aclList = [])
    {
        $this->acl = new Acl();
        $this->role = $role;

        if (isset($aclList['resources'])) {
            foreach ($aclList['resources'] as $resource) {
                $this->acl->addResource($this->makeResource($resource));
            }
        }

        if (isset($aclList['roles'])) {
            foreach ($aclList['roles'] as $role) {
                $this->acl->addRole($this->makeRole($role));
            }
        }

        if (isset($aclList['assignments'])) {
            foreach ($aclList['assignments']['allow'] as $role => $resources) {
                foreach ($resources as $resource) {
                    $this->addAllow($role, $resource);
                }
            }

            foreach ($aclList['assignments']['deny'] as $role => $resources) {
                foreach ($resources as $resource) {
                    $this->addDeny($role, $resource);
                }
            }
        }
    }

    /**
     * @param string $id
     * @return \Zend\Permissions\Acl\Resource\GenericResource
     */
    public function makeResource($id = '') {
        return new Resource($id);
    }

    /**
     * @param string $id
     * @return \Zend\Permissions\Acl\Role\GenericRole
     */
    public function makeRole($id = '') {
        return new Role($id);
    }

    /**
     * @param \Zend\Permissions\Acl\Role\GenericRole $role
     * @param array                                  $parents
     */
    public function addRole(Role $role, array $parents = []) {
        $this->acl->addRole($role, $parents);
    }

    /**
     * @param string $role_id
     * @param string $resource_id
     */
    public function addDeny($role_id = '', $resource_id = '') {
        $this->acl->deny($role_id, $resource_id);
    }

    /**
     * @param string $role_id
     * @param string $resource_id
     */
    public function addAllow($role_id = '', $resource_id = '') {
        $this->acl->allow($role_id, $resource_id);
    }

    /**
     * @param string $role_id
     * @param string $resource_id
     * @return bool
     */
    public function isAllowed($role_id = '', $resource_id = '') {
        return $this->acl->isAllowed($role_id, $resource_id);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $requestInterface
     * @param \Psr\Http\Message\ResponseInterface      $responseInterface
     * @param callable                                 $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $requestInterface, ResponseInterface $responseInterface, callable $next) {
        $allowed = false;
        foreach ($this->role as $role) {
            if ($this->isAllowed($role, $requestInterface->getUri()->getPath())) {
                $allowed = true;
            }
        }

        if ($allowed) {
            return $next($requestInterface, $responseInterface);
        } else {
            return $responseInterface->withStatus(401);
        }
    }
}