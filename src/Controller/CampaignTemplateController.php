<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Service\CampaignTemplateService;
use CRMAIze\Service\AuthService;

class CampaignTemplateController
{
    private $twig;
    private $templateService;
    private $authService;

    public function __construct(Application $app)
    {
        $this->twig = $app->getTwig();
        $this->templateService = $app->getCampaignTemplateService();
        $this->authService = $app->getAuthService();
    }

    public function index(): Response
    {
        $this->requireAuth();

        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';

        if ($search) {
            $templates = $this->templateService->searchTemplates($search);
        } elseif ($category) {
            $templates = $this->templateService->getTemplatesByCategory($category);
        } else {
            $templates = $this->templateService->getPublicTemplates();
        }

        $categories = $this->templateService->getCategories();
        $popularTemplates = $this->templateService->getPopularTemplates(5);

        $content = $this->twig->render('campaign_templates.twig', [
            'templates' => $templates,
            'categories' => $categories,
            'popularTemplates' => $popularTemplates,
            'currentCategory' => $category,
            'searchQuery' => $search,
            'current_page' => 'templates'
        ]);

        return new Response($content);
    }

    public function show(int $id): Response
    {
        $this->requireAuth();

        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return new Response('Template not found', 404);
        }

        $content = $this->twig->render('campaign_template_detail.twig', [
            'template' => $template,
            'current_page' => 'templates'
        ]);

        return new Response($content);
    }

    public function create(): Response
    {
        $this->requireAuth();

        $categories = $this->templateService->getCategories();

        $content = $this->twig->render('campaign_template_form.twig', [
            'categories' => $categories,
            'current_page' => 'templates'
        ]);

        return new Response($content);
    }

    public function store(Request $request): Response
    {
        $this->requireAuth();

        $data = $request->getPost();
        $data['created_by'] = $this->authService->getCurrentUserId();

        try {
            $templateId = $this->templateService->createTemplate($data);

            header('Location: /templates/' . $templateId);
            exit;
        } catch (\Exception $e) {
            $categories = $this->templateService->getCategories();

            $content = $this->twig->render('campaign_template_form.twig', [
                'categories' => $categories,
                'error' => $e->getMessage(),
                'data' => $data,
                'current_page' => 'templates'
            ]);

            return new Response($content);
        }
    }

    public function edit(int $id): Response
    {
        $this->requireAuth();

        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return new Response('Template not found', 404);
        }

        // Check if user owns the template or is admin
        if ($template->created_by !== $this->authService->getCurrentUserId() &&
            $this->authService->getCurrentUserRole() !== 'admin') {
            return new Response('Unauthorized', 403);
        }

        $categories = $this->templateService->getCategories();

        $content = $this->twig->render('campaign_template_form.twig', [
            'template' => $template,
            'categories' => $categories,
            'current_page' => 'templates'
        ]);

        return new Response($content);
    }

    public function update(Request $request, int $id): Response
    {
        $this->requireAuth();

        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return new Response('Template not found', 404);
        }

        // Check if user owns the template or is admin
        if ($template->created_by !== $this->authService->getCurrentUserId() &&
            $this->authService->getCurrentUserRole() !== 'admin') {
            return new Response('Unauthorized', 403);
        }

        $data = $request->getPost();

        try {
            $this->templateService->updateTemplate($id, $data);

            header('Location: /templates/' . $id);
            exit;
        } catch (\Exception $e) {
            $categories = $this->templateService->getCategories();

            $content = $this->twig->render('campaign_template_form.twig', [
                'template' => $template,
                'categories' => $categories,
                'error' => $e->getMessage(),
                'current_page' => 'templates'
            ]);

            return new Response($content);
        }
    }

    public function delete(int $id): Response
    {
        $this->requireAuth();

        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return new Response('Template not found', 404);
        }

        // Check if user owns the template or is admin
        if ($template->created_by !== $this->authService->getCurrentUserId() &&
            $this->authService->getCurrentUserRole() !== 'admin') {
            return new Response('Unauthorized', 403);
        }

        $this->templateService->deleteTemplate($id);

        header('Location: /templates');
        exit;
    }

    public function useTemplate(int $id): Response
    {
        $this->requireAuth();

        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return new Response('Template not found', 404);
        }

        $campaignData = $this->templateService->createFromTemplate($id);

        // Redirect to campaign creation with template data
        $queryString = http_build_query($campaignData);
        header('Location: /campaigns/create?' . $queryString);
        exit;
    }

    public function rateTemplate(Request $request, int $id): Response
    {
        $this->requireAuth();

        $rating = (float)$request->getPost()['rating'];

        if ($rating < 1 || $rating > 5) {
            return new Response('Invalid rating', 400);
        }

        $this->templateService->rateTemplate($id, $rating);

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }

    public function seed(): Response
    {
        $this->requireAuth();

        // Only allow admins to seed templates
        if ($this->authService->getCurrentUserRole() !== 'admin') {
            return new Response('Unauthorized', 403);
        }

        $this->templateService->seedDefaultTemplates();

        header('Location: /templates?seeded=1');
        exit;
    }

    private function requireAuth(): void
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
}

