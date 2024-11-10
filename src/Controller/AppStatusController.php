<?php

namespace App\Controller;

use App\Command\AbstractCommand;
use App\Services\GitDriver;
use OpenApi\Attributes\JsonContent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

/**
 * Class AppStatusController
 *
 * @package AppBundle\Controller
 */
class AppStatusController extends AbstractController
{

    /**
     * Status of the application.
     *
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     */
    #[OA\Response(
      response: 200,
      description: 'Returns the status of the application'
    )]
    #[OA\Tag(name: "Api status endpoints")]
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
     * Builds a command string
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     *
     * @return JsonResponse
     */
    #[OA\Response(
      response: 200,
      description: 'Returns api:install command'
    )]
    #[OA\Tag(name: "Api build command")]
    public function buildCommandAction($commandName, KernelInterface $kernel, Request $request)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

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
     * Checks for platforms updates.
     *
     * @param \App\Services\GitDriver $gitDriver
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[OA\Tag(name: "Api status endpoints")]
    public function checkUpdatesAction(GitDriver $gitDriver)
    {

        $output = $gitDriver->fetchAll('.');

        return $this->json(['message' => $output]);
    }

    /**
     * Gets current platform tag.
     *
     * @param GitDriver $gitDriver
     *
     * @return JsonResponse
     */
    #[OA\Response(
      response: 200,
      description: 'Returns current platform tag',
    )]
    #[OA\Tag(name: "Api status endpoints")]
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
     * Gets a list of platform tags.
     *
     * @param GitDriver $gitDriver
     *
     * @return JsonResponse
     */
    #[OA\Tag(name: "Api status endpoints")]
    #[OA\Response(
      response: 200,
      description: 'Gets a list of platform tags',
    )]
    public function availableTagsAction(GitDriver $gitDriver)
    {
        $tags = $gitDriver->getTagsList('.');

        return $this->json(['tags' => array_reverse($tags)]);
    }

    /**
     * Switch platform tag.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Services\GitDriver $gitDriver
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[OA\Response(
      response: 200,
      description: 'Switch platform for specified tag',
    )]
    #[OA\Tag(name: "Api status endpoints")]
    #[OA\Parameter(
      name: 'Tag',
      description: 'Git tag of a platform',
      in: 'query',
      schema: new OA\Schema(type: 'string'),
    )]
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

    /**
     * Perform 'drush uli command'
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[OA\Response(
      response: 200,
      description: 'Returns drush uli for requested site',
    )]
    #[OA\Tag(name: "Api status endpoints")]
    #[OA\Parameter(
      name: 'site',
      description: 'Site name',
      in: 'query',
      schema: new OA\Schema(type: 'string'),
    )]
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
