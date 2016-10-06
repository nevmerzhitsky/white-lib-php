<?php

/**
 * Dependency injection container.
 *
 * Example of usage:
 * <?php
 * $container = new Svc_Di;
 *
 * $container->setFactory(DbAdapter::class, function() {
 *     return new DbAdapter('mysql:dbname=testdb;host=127.0.0.1', 'dbuser', 'dbpass');
 * });
 *
 * $container->setFactory(UserRepository::class, function(Svc_Di $container) {
 *     $dbAdapter = $container->get(DbAdapter::class);
 *     return new UserRepository($dbAdapter);
 * });
 *
 * // To create singleton (shared instance)
 * Svc_Di::getInstance()->setSingletonFactory(Svc_UrlFetcher::class, function() {
 *     return new Svc_UrlFetcher();
 * });
 *
 * // Then for mocking in a unit-test:
 * public function testRequestWithForBarParameters()
 * {
 *     Svc_Di::getInstance()->setSingletonFactory(Svc_UrlFetcher::class, function() {
 *         return $this->_createMock($this->_generateResponseXml('foo', 'bar'));
 *     });
 *     // Call system under test
 * }
 * ?>
 *
 * @link https://mwop.net/blog/260-Dependency-Injection-An-analogy.html
 * @link http://ralphschindler.com/2011/05/18/learning-about-dependency-injection-and-php
 * @link http://fabien.potencier.org/what-is-dependency-injection.html
 * @link Inspired by http://mattallan.org/2016/dependency-injection-containers/
 */
class Svc_Di
{
    /**
     * @var \Closure[]
     */
    private $_entries = [];

    /**
     * Adds an entry to the container.
     *
     * @param string $id Identifier of the entry.
     * @param \Closure $value The closure to invoke when this entry is resolved.
     */
    public function setFactory($id, \Closure $value)
    {
        $this->_entries[$id] = $value;
    }

    /**
     * Adds a shared (singleton) entry to the container.
     *
     * @param string $id Identifier of the entry.
     * @param \Closure $value The closure to invoke when this entry is resolved.
     */
    public function setSingletonFactory($id, \Closure $value)
    {
        $this->setFactory($id, function ($container) use ($value) {
            static $resolvedValue;

            if (is_null($resolvedValue)) {
                $resolvedValue = $value($container);
            }

            return $resolvedValue;
        });
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed The resolved value for the entry.
     * @throws DiNotFoundException No entry was found for this identifier.
     */
    public function get($id)
    {
        if (!array_key_exists($id, $this->_entries)) {
            throw new DiNotFoundException(
                sprintf('The entry for %s was not found.', $id)
            );
        }

        $args = func_get_args();
        // Pass in also the container as the argument to the closure.
        array_unshift($args, $this);

        return call_user_func_array($this->_entries[$id], $args);
    }
}
