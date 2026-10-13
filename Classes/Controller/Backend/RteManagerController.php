<?php

declare(strict_types=1);

namespace Kst3\RteManager\Controller\Backend;

use Kst3\RteManager\Service\RteSettings;
use Kst3\RteManager\Service\HostedFonts;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

#[AsController]
final readonly class RteManagerController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private RteSettings $rteSettings,
        private CacheManager $cacheManager,
        private HostedFonts $hostedFonts,
        private PolicyRegistry $policyRegistry,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Sandboxed previews embed selected font files before they are uploaded.
        $this->policyRegistry->appendMutationCollection(new MutationCollection(
            new Mutation(MutationMode::Extend, Directive::FontSrc, SourceScheme::data),
        ));
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class)->addInlineLanguageLabelFile('EXT:rte_manager/Resources/Private/Language/locallang.xlf', 'js.rteManager.');
        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle(\Kst3\RteManager\Service\Localization::translate('label.rte.manager', 'RTE Manager'));
        $view->makeDocHeaderModuleMenu();
        $settings = $this->rteSettings->getAll();

        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            $settings = is_array($body) && is_array($body['rte'] ?? null) ? $body['rte'] : [];
            try {
                if (!is_array($body) || ($body['rteForm'] ?? '') !== '1') {
                    $settings = $this->rteSettings->getAll();
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.the.form.was.not.received.completely.the.uploads.may.exceed.the.server.upload.limit.s', 'The form was not received completely. The uploads may exceed the server upload limit. Select smaller files and try again.'));
                }
                $normalized = $this->rteSettings->normalize($settings);
                $fontRows = $settings['fonts'] ?? [];
                $fontUploads = $request->getUploadedFiles()['fontFiles'] ?? [];
                if (!is_array($fontRows) || !is_array($fontUploads)) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.font.upload.data', 'Invalid font upload data.'));
                }
                $normalized['fonts'] = $this->hostedFonts->prepare($fontRows, $fontUploads);
                $defaultFont = $settings['defaultFont'] ?? [];
                if (!is_array($defaultFont)) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.default.font', 'Invalid default font.'));
                if (!empty($defaultFont['clear'])) {
                    $normalized['defaultFont'] = [];
                } else {
                    $upload = $request->getUploadedFiles()['defaultFontFile'] ?? null;
                    $hasFile = !empty($defaultFont['file']) || ($upload !== null && $upload->getError() !== UPLOAD_ERR_NO_FILE);
                    $defaultFont['family'] = $hasFile ? 'RteDefaultFont' : '';
                    $normalized['defaultFont'] = $this->hostedFonts->prepare([$defaultFont], [$upload])[0] ?? [];
                }
                $settings = $normalized;
                $this->rteSettings->save($settings);
                $this->cacheManager->flushCaches();
                $view->addFlashMessage(\Kst3\RteManager\Service\Localization::translate('label.rte.preset.saved.caches.were.flushed', 'RTE preset saved. Caches were flushed.'), '', ContextualFeedbackSeverity::OK);
            } catch (\InvalidArgumentException $exception) {
                $view->addFlashMessage($exception->getMessage(), \Kst3\RteManager\Service\Localization::translate('label.rte.settings.not.saved', 'RTE settings not saved'), ContextualFeedbackSeverity::ERROR);
            } catch (IOExceptionInterface $exception) {
                $view->addFlashMessage(\Kst3\RteManager\Service\Localization::translate('label.files.could.not.be.written.check.write.permissions.for.fileadmin.rte.manager.and.its', 'Files could not be written. Check write permissions for fileadmin/rte_manager and its Configuration, fonts and styles directories.'), \Kst3\RteManager\Service\Localization::translate('label.rte.settings.not.saved', 'RTE settings not saved'), ContextualFeedbackSeverity::ERROR);
            }
        }

        $styleFields = \Kst3\RteManager\Service\ElementDefaults::rows(array_replace($this->rteSettings->getAll(), ['elementDefaults' => []]))[0]['fields'];
        foreach ($settings['styles'] ?? [] as $index => $style) {
            $settings['styles'][$index]['elementOptions'] = $this->rteSettings->getElementOptions($style['elements'] ?? []);
            $fields = $styleFields;
            foreach ($fields as &$field) {
                foreach ($field['options'] as &$option) {
                    $option['selected'] = (string)$option['value'] === ($style[$field['name']] ?? '');
                }
                unset($option);
            }
            unset($field);
            $settings['styles'][$index]['formatFields'] = $fields;
        }

        $defaultRows = \Kst3\RteManager\Service\ElementDefaults::rows($this->rteSettings->getAll());
        foreach ($defaultRows as &$row) {
            $submitted = $settings['elementDefaults'][$row['element']] ?? [];
            if (!is_array($submitted)) continue;
            if (isset($submitted['css']) && is_string($submitted['css'])) $row['css'] = $submitted['css'];
            foreach ($row['fields'] as &$field) {
                if (!isset($submitted[$field['name']]) || !is_string($submitted[$field['name']])) continue;
                foreach ($field['options'] as &$option) {
                    $option['selected'] = (string)$option['value'] === $submitted[$field['name']];
                }
                unset($option);
            }
            unset($field);
        }
        unset($row);
        return $view->assign('rte', $settings)
            ->assign('fontPreviewBase', $request->getAttribute('normalizedParams')->getSitePath() . HostedFonts::DIRECTORY)
            ->assign('defaultRows', $defaultRows)
            ->assign('styleFields', $styleFields)
            ->assign('sizeElements', ['html' => \Kst3\RteManager\Service\Localization::translate('label.default.html', 'Default (html)')])
            ->assign('elementOptions', $this->rteSettings->getElementOptions())
            ->assign('fontWeights', ['400' => \Kst3\RteManager\Service\Localization::translate('label.400.regular', '400 (Regular)'), '100' => '100', '200' => '200', '300' => '300', '500' => '500', '600' => '600', '700' => \Kst3\RteManager\Service\Localization::translate('label.700.bold', '700 (Bold)'), '800' => '800', '900' => '900', '100 900' => \Kst3\RteManager\Service\Localization::translate('label.variable.100.900', 'Variable (100–900)')])
            ->assign('fontStyles', ['normal' => \Kst3\RteManager\Service\Localization::translate('label.normal', 'Normal'), 'italic' => \Kst3\RteManager\Service\Localization::translate('label.italic', 'Italic')])
            ->renderResponse('Backend/RteManager');
    }
}
