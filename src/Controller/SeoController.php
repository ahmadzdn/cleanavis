<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SeoController extends AbstractController
{
    private function absoluteOrigin(Request $request): string
    {
        return $request->getSchemeAndHttpHost();
    }

    #[Route('/robots.txt', name: 'seo_robots', methods: ['GET'])]
    public function robots(Request $request): Response
    {
        $origin = $this->absoluteOrigin($request);

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /webhooks/',
            'Disallow: /paiement/',
            '',
            'Sitemap: '.$origin.$this->generateUrl('seo_sitemap', [], UrlGeneratorInterface::ABSOLUTE_PATH),
            '',
        ];

        return new Response(implode("\n", $lines), Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    #[Route('/sitemap.xml', name: 'seo_sitemap', methods: ['GET'])]
    public function sitemap(Request $request): Response
    {
        $origin = $this->absoluteOrigin($request);
        $urls = [
            ['loc' => $origin.'/', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => $origin.$this->generateUrl('page_contact', [], UrlGeneratorInterface::ABSOLUTE_PATH), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => $origin.$this->generateUrl('page_cgs', [], UrlGeneratorInterface::ABSOLUTE_PATH), 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        $entries = [];
        foreach ($urls as $u) {
            $entries[] = sprintf(
                '  <url><loc>%s</loc><changefreq>%s</changefreq><priority>%s</priority></url>',
                htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $u['changefreq'],
                $u['priority']
            );
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= implode("\n", $entries)."\n";
        $xml .= '</urlset>';

        return new Response($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
