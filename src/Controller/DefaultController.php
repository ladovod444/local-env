<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AppStatusController
 *
 * @package AppBundle\Controller
 */

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app-home')]
    public function indexAction()
    {
        return $this->render('base.html.twig');
    }
}
