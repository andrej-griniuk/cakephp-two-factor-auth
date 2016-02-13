<?php
namespace TwoFactorAuth\Test\App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * AuthUser class
 *
 */
class AuthUsersTable extends Table
{

    /**
     * Custom finder
     *
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options to find with
     * @return \Cake\ORM\Query The query builder
     */
    public function findAuth(Query $query, array $options)
    {
        $query->select(['id', 'username', 'password', 'secret']);

        return $query;
    }
}
