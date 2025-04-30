<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\FileExplorerBundle\Controller;

use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('/tree', name: 'pimcore_bundle_file_explorer_settings_tree', methods: ['GET'])]
    public function treeAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');
        $referencePath = $this->getFileExplorerPath($request, 'node');

        $items = scandir($referencePath);
        $contents = [];

        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $file = $referencePath . '/' . $item;
            $file = str_replace('//', '/', $file);

            if (is_dir($file) || is_file($file)) {
                $itemConfig = [
                    'id' => '/fileexplorer' . str_replace(PIMCORE_PROJECT_ROOT, '', $file),
                    'text' => $item,
                    'leaf' => true,
                    'writeable' => is_writable($file),
                ];

                if (is_dir($file)) {
                    $itemConfig['leaf'] = false;
                    $itemConfig['type'] = 'folder';
                    if (is_dir_empty($file)) {
                        $itemConfig['loaded'] = true;
                    }
                    $itemConfig['expandable'] = true;
                } elseif (is_file($file)) {
                    $itemConfig['type'] = 'file';
                }

                $contents[] = $itemConfig;
            }
        }

        return $this->jsonResponse($contents);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('/content', name: 'pimcore_bundle_file_explorer_settings_content', methods: ['GET'])]
    public function contentAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;
        $writeable = false;
        $file = $this->getFileExplorerPath($request, 'path');
        $content = null;
        if (is_file($file)) {
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $success = true;
                $writeable = is_writable($file);
            }
        }

        return $this->jsonResponse([
            'success' => $success,
            'content' => $content,
            'writeable' => $writeable,
            'filename' => basename($file),
            'path' => preg_replace('@^' . preg_quote(PIMCORE_PROJECT_ROOT, '@') . '@', '', $file),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('/content-save', name: 'pimcore_bundle_file_explorer_settings_contentsave', methods: ['PUT'])]
    public function contentSaveAction(Request $request, Filesystem $filesystem): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('content') && $request->get('path')) {
            $file = $this->getFileExplorerPath($request, 'path');
            if (is_file($file) && is_writable($file)) {
                $filesystem->dumpFile($file, $request->get('content'));

                $success = true;
            }
        }

        return $this->jsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    #[Route('/add', name: 'pimcore_bundle_file_explorer_settings_add', methods: ['POST'])]
    public function addAction(Request $request, Filesystem $filesystem): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('filename') && $request->get('path')) {
            $path = $this->getFileExplorerPath($request, 'path');
            $file = $path . '/' . $request->get('filename');

            $file = resolvePath($file);
            if (strpos($file, PIMCORE_PROJECT_ROOT) !== 0) {
                throw new \Exception('not allowed');
            }

            if (is_writable(dirname($file))) {
                $filesystem->dumpFile($file, '');

                $success = true;
            }
        }

        return $this->jsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    #[Route('/add-folder', name: 'pimcore_bundle_file_explorer_settings_addfolder', methods: ['POST'])]
    public function addFolderAction(Request $request, Filesystem $filesystem): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('filename') && $request->get('path')) {
            $path = $this->getFileExplorerPath($request, 'path');
            $file = $path . '/' . $request->get('filename');

            $file = resolvePath($file);
            if (strpos($file, PIMCORE_PROJECT_ROOT) !== 0) {
                throw new \Exception('not allowed');
            }

            if (is_writable(dirname($file))) {
                $filesystem->mkdir($file);

                $success = true;
            }
        }

        return $this->jsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('/delete', name: 'pimcore_bundle_file_explorer_settings_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');
        $success = false;

        if ($request->get('path')) {
            $file = $this->getFileExplorerPath($request, 'path');
            if (is_writable($file)) {
                unlink($file);
                $success = true;
            }
        }

        return $this->jsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('/rename', name: 'pimcore_bundle_file_explorer_settings_rename', methods: ['PUT'])]
    public function renameAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');
        $success = false;

        if ($request->get('path') && $request->get('newPath')) {
            $file = $this->getFileExplorerPath($request, 'path');
            $newFile = $this->getFileExplorerPath($request, 'newPath');

            $success = rename($file, $newFile);
        }

        return $this->jsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @throws \Exception
     *
     * @psalm-taint-specialize
     */
    private function getFileExplorerPath(Request $request, string $paramName = 'node'): string
    {
        $path = preg_replace("/^\/fileexplorer/", '', $request->get($paramName));
        $path = resolvePath(PIMCORE_PROJECT_ROOT . $path);

        if (strpos($path, PIMCORE_PROJECT_ROOT) !== 0) {
            throw new \Exception('operation permitted, permission denied');
        }

        return $path;
    }
}
