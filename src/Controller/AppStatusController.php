<?php

namespace App\Controller;

use App\Command\AbstractCommand;
use App\Services\GitDriver;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class AppStatusController
 *
 * @package AppBundle\Controller
 */
class AppStatusController extends AbstractController
{

    /**
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     */
    public function statusAction(SerializerInterface $serializer)
    {
        $parameters = [
            $this->buildKv('PHP version', phpversion()),
            $this->buildKv('Ruby version', shell_exec('ruby --version')),
            $this->buildKv('Node JS version', shell_exec('node --version')),
            $this->buildKv('NPM version', shell_exec('npm --version')),
        ];

        return $this->json($parameters, Response::HTTP_OK, ['Access-Control-Allow-Origin' => '*']);
    }

    /**
     * @param string          $commandName
     * @param KernelInterface $kernel
     * @param Request         $request
     *
     * @return JsonResponse
     */
    public function buildCommandAction($commandName, KernelInterface $kernel, Request $request)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $d = 1;
        //dump($application->find($commandName)); die();

        try {
            /** @var AbstractCommand $command */
            $command = $application->find($commandName);
        } catch (\Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $arguments = [];
        $inputDefinition = $command->getDefinition();
        $data = json_decode($request->getContent());

        foreach ($inputDefinition->getArguments() as $k => $v) {
            if (isset($data->{$k})){
                $arguments[] = $data->{$k};
            }
        }

        $options = [];
        foreach ($inputDefinition->getOptions() as $k => $v) {
            if (isset($data->{$k})) {
                $options[] = sprintf('--%s=%s', $k, escapeshellarg($data->{$k}));
            }

        }

        $cmd = strtr(
            'php ./bin/console %cmd% %args% %opts%',
            [
                '%cmd%' => $commandName,
                '%args%' => implode(' ', $arguments),
                '%opts%' => implode(' ', $options),
            ]
        );

        $res = $this->json(
          [
            'command' => $cmd,
          ]
        );

          return $res;

        return $this->json(
            [
                'command' => $cmd,
            ]
        );
    }

    /**
     * @param \App\Services\GitDriver $gitDriver
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkUpdatesAction(GitDriver $gitDriver)
    {

        $output = $gitDriver->fetchAll('.');

        return $this->json(['message' => $output]);
    }

    /**
     * @param GitDriver $gitDriver
     *
     * @return JsonResponse
     */
    public function currentTagAction(GitDriver $gitDriver)
    {
        $tag = $gitDriver->getCurrentTag('.');

        return $this->json(['tag' => $tag]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function phpinfoAction()
    {
        phpinfo();
        return new Response();
    }

    /**
     * @param GitDriver $gitDriver
     *
     * @return JsonResponse
     */
    public function availableTagsAction(GitDriver $gitDriver)
    {
        $tags = $gitDriver->getTagsList('.');

        return $this->json(['tags' => array_reverse($tags)]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Services\GitDriver $gitDriver
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function switchTagAction(Request $request, GitDriver $gitDriver)
    {
        $json = $request->getContent();
        $data = json_decode($json);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyPath = 'values.new_tag';
        if (false == $propertyAccessor->isReadable($data, $propertyPath)) {
            return $this->json([], Response::HTTP_BAD_REQUEST);
        }
        $tag = $propertyAccessor->getValue($data, $propertyPath);

        $gitDriver->checkoutTag('.', $tag);

        return $this->json(['tag' => $tag]);
    }

    private function buildKv($key, $value)
    {
        return [
            'key' => $key,
            'value' => $value,
        ];
    }

    public function drushUliAction(Request $request)
    {
        $data = $request->getContent();
        $data = json_decode($data, true);
        preg_match('/(.*)_.*/', $data['id'], $matches);
        $id = str_replace('-', '_', $matches[1]);
        exec('cd '. $data['location'] .'/docroot/sites/'. $id .' && drush uli', $output);
        $drush_uli_link = preg_replace('/.*'. $id .'/', '', $output);
        return $this->json(
            [
                'drush_uli_link' => end($drush_uli_link),
            ]
        );
    }
}
