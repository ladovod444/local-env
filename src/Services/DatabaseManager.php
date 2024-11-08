<?php

namespace App\Services;

use App\Model\ConsoleCommand;
use App\Process\ProcessAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class DatabaseManager
 *
 * @package AppBundle\Services
 */
class DatabaseManager
{
    use ProcessAwareTrait;

    /** @var EntityManagerInterface */
    private $em;
    /** @var SiteInfoExtractor */
    private $siteInfoExtractor;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * DatabaseManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param SiteInfoExtractor $siteInfoExtractor
     * @param Filesystem $filesystem
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SiteInfoExtractor $siteInfoExtractor,
        Filesystem $filesystem
    ) {

        $this->em = $entityManager;
        $this->siteInfoExtractor = $siteInfoExtractor;
        $this->filesystem = $filesystem;
    }

    /**
     * Import database.
     *
     * @param string $dbName
     * @param string $pathToFile
     */
    public function import($dbName, $pathToFile)
    {

        $import = sprintf(
            'zcat %s | mysql -u%s -p%s %s',
            $pathToFile,
            $this->em->getConnection()->getUsername(),
            $this->em->getConnection()->getPassword(),
            $dbName
        );

        $process = new Process($import);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Check if database exist.
     *
     * @param string $databaseName
     * @return bool
     */
    public function databaseExist($databaseName)
    {
        $databasesList = $this->getSchemaManager()->listDatabases();
        return in_array($databaseName, $databasesList);
    }

    /**
     * Create database.
     *
     * @param string $databaseName
     */
    public function create($databaseName)
    {
        $this->getSchemaManager()->createDatabase($databaseName);
        $this->em->flush();
    }


    /**
     * Truncate database.
     *
     * @param $databaseName
     */
    public function truncate($databaseName)
    {
        $this->getSchemaManager()->dropAndCreateDatabase($databaseName);
        $this->em->flush();
    }

    /**
     * @param string $databaseName
     */
    public function deleteDatabase($databaseName)
    {
        $this->getSchemaManager()->dropDatabase($databaseName);
        $this->em->flush();
    }


    /**
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    private function getSchemaManager(){
        $conn = $this->em->getConnection();
        $sm = $conn->getSchemaManager();
        return $sm;
    }

    /**
     * Make database clone.
     *
     * @param $databaseName
     *
     * @return null|string
     * @throws \Exception
     */
    public function makeClone($databaseName)
    {
        try {
            $newDatabaseName = $this->buildNewDatabaseName($databaseName);

            $this->getSchemaManager()->createDatabase($newDatabaseName);
            $this->em->flush();

            $this->cloneDatabaseData($databaseName, $newDatabaseName);

            $this->em->flush();
        } catch (\Exception $exception) {
            if (!empty($newDatabaseName)) {
                $this->deleteDatabase($newDatabaseName);
            }

            throw $exception;
        }

        return $newDatabaseName;
    }

    /**
     * @param $databaseName
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getClonesCount($databaseName)
    {
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare("SHOW DATABASES LIKE :db_name");
        $stmt->bindValue('db_name', sprintf($this->getDatabaseCloneNamePattern(), $databaseName).'%');
        $stmt->execute();
        $clonesCount = $stmt->rowCount();

        return $clonesCount;
    }

    public function getDatabaseClonesNames($databaseName){
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare("SHOW DATABASES LIKE :db_name");
        $stmt->bindValue('db_name', sprintf($this->getDatabaseCloneNamePattern(), $databaseName).'%');
        $stmt->execute();
        $clonesCount = $stmt->fetchAll(\PDO::FETCH_FUNC, function ($item){
            return $item;
        });

        return array_values($clonesCount);
    }

    /**
     * @param $databaseName
     * @return null|string
     *
     * @throws \Exception
     */
    private function buildNewDatabaseName($databaseName)
    {
        if ($this->databaseExist($databaseName) == false) {
            $message = sprintf('Could not clone database "%s", because it does not exist.', $databaseName);
            throw new \Exception($message);
        }

        try {
            $clonesCount = $this->getClonesCount($databaseName);

            return sprintf($this->getNewCloneDatabaseNamePattern(), $databaseName, $clonesCount);
        } catch (\Exception $exception) {
            // @TODO Handle.
        }

        return null;
    }

    private function getDatabaseCloneNamePattern()
    {
        return '%s_clone';
    }

    private function getNewCloneDatabaseNamePattern()
    {
        return $this->getDatabaseCloneNamePattern().'_%d';
    }

    /**
     * @param $originDatabaseName
     * @param $destinationDatabaseName
     */
    private function cloneDatabaseData($originDatabaseName, $destinationDatabaseName)
    {
        $conn = $this->em->getConnection();
        $dbDumpName = $this->buildUniqueDumpName();

        $cmd = strtr(
            'mysqldump -u%login% -p%password% %origin% > %dump_name% && mysql -u%login% -p%password% %destination% < %dump_name%',
            [
                '%login%' => $conn->getUsername(),
                '%password%' => $conn->getPassword(),
                '%host%' => $conn->getHost(),
                '%origin%' => $originDatabaseName,
                '%destination%' => $destinationDatabaseName,
                '%dump_name%' => $dbDumpName,
            ]);

        $process = new Process($cmd);
        $process->setTimeout(ConsoleCommand::PROCESS_TIMEOUT);
        $process->run($this->getRealTimeProcessCallback());
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if($this->filesystem->exists($dbDumpName)){
            $this->filesystem->remove($dbDumpName);
        }
    }

    private function buildUniqueDumpName(){
        return sprintf('/tmp/db_%s.sql', uniqid());
    }
}
