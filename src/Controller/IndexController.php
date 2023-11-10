<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController
{
    #[Route('/', name: 'index')]
    public function number(): Response
    {
        return new Response(
            '<html><body>Hello there, I am deployed via AutoGit 🤗</body></html>'
        );
    }
}
