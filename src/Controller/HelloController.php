<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HelloController extends AbstractController {
  #[Route(path: ['/', '/home'], name: 'app', methods: ['GET'])]
  public function index(): Response {
    # code...
    return $this->render("home/index.html.twig", [
      "hello" => "hello, world",
    ]);
  }
}

