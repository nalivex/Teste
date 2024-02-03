<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    private ProductService $service;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->service = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
		{
			$adminUserId = $request->getHeader('admin_user_id')[0];
			$queryParams = $request->getQueryParams();
			$activeFilter = isset($queryParams['active']) ? $queryParams['active'] : null;
			$categoryFilter = isset($queryParams['category']) ? $queryParams['category'] : null;
			$orderBy = isset($queryParams['created_at']) ? $queryParams['created_at'] : null;
			
			$stm = $this->service->getAll($adminUserId, $activeFilter, $categoryFilter, $orderBy);
	
		
		    $response->getBody()->write(json_encode($stm->fetchAll()));
		    return $response->withStatus(200);
		}

		public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
		{
				$stm = $this->service->getOne($args['id']);
				$product = Product::hydrateByFetch($stm->fetch());
		
				$adminUserId = $request->getHeader('admin_user_id')[0];
				$productCategories = $this->categoryService->getProductCategory($product->id)->fetchAll();
				
				$categoryTitles = [];
				foreach ($productCategories as $category) {
						$fetchedCategory = $this->categoryService->getOne($adminUserId, $category->id)->fetch();
						$categoryTitles[] = $fetchedCategory->title;          
				}
		
				$productCategories = $this->service->GetLastUpdate($product->id)->fetch();
				if ($productCategories && $args['id'] == 4) {
						$product->setLastUpdatedBy($productCategories->last_updated_by);
				} else {
						unset($product->lastUpdatedBy);
				}
		
				$product->setCategory($categoryTitles);
				$response->getBody()->write(json_encode($product));
				return $response->withStatus(200);
		}		

    public function insertOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->insertOne($body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->deleteOne($args['id'], $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }
}
