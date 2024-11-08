<?php

namespace App\Monolog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use Symfony\Component\Console\Helper\FormatterHelper;

/**
 * Class Console
 *
 * @package AppBundle\Monolog\Formatter
 */
class Console implements FormatterInterface
{

    /**
     * @var \Monolog\Formatter\FormatterInterface
     */
    private $formatter;

    public function __construct()
    {
        $this->formatter = new FormatterHelper();
    }

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     *
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        if(empty($record['message'])){
            return PHP_EOL;
        }
        /** @var \DateTime $date */
        $date = $record['datetime'];
        return sprintf('%s: %s', $date->format('d.m.y h:i:s'), $record['message']).PHP_EOL;
        $message = '';
        switch ($record['level']) {
            case Logger::INFO:
                $message .= $this->formatter->formatBlock(
                    $record['message'],
                    'info'
                );
                break;
            case Logger::NOTICE:
                $message .= $this->formatter->formatBlock(
                    $record['message'],
                    'info'
                );
                break;
            case Logger::WARNING:
                $message .= $this->formatter->formatBlock(
                    '[WARNING]'.$record['message'],
                    'ERROR',
                    true
                );
                break;
            case Logger::ERROR:
                $message .= $this->formatter->formatBlock(
                    ['Error!', $record['message']],
                    'ERROR',
                    true
                );

                break;
            case Logger::CRITICAL:
            case Logger::ALERT:
            case Logger::EMERGENCY:
                $message .= $this->formatter->formatBlock(
                    '[CRITICAL]'.$record['message'],
                    'ERROR',
                    true
                );

                break;

        }

        return $message.PHP_EOL;
    }

    /**
     * Formats a set of log records.
     *
     * @param  array $records A set of records to format
     *
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }
}
