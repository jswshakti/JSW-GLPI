<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\FormTagsManager;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Form\Tag\SectionTagProvider;
use InvalidArgumentException;
use JsonConfigInterface;
use Override;

class ContentField extends AbstractConfigField
{
    #[Override]
    public function getKey(): string
    {
        return 'content';
    }

    #[Override]
    public function getLabel(): string
    {
        return __("Content");
    }

    #[Override]
    public function getConfigClass(): string
    {
        return SimpleValueConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonConfigInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof SimpleValueConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.textareaField(
                input_name,
                value,
                label,
                options|merge({
                    'enable_richtext': true,
                    'enable_images': false,
                    'enable_form_tags': true,
                    'form_tags_form_id': form_id
                })
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'form_id'    => $form->fields['id'],
            'label'      => $this->getLabel(),
            'value'      => $config->getValue(),
            'input_name' => $input_name . "[" . SimpleValueConfig::VALUE . "]",
            'options'    => $display_options,
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonConfigInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof SimpleValueConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $tag_manager = new FormTagsManager();
        $input['content'] = $tag_manager->insertTagsContent(
            $config->getValue(),
            $answers_set
        );

        return $input;
    }

    #[Override]
    public function supportAutoConfiguration(): bool
    {
        return true;
    }

    #[Override]
    public function getAutoGeneratedConfig(Form $form): SimpleValueConfig
    {
        $section_provider = new SectionTagProvider();
        $question_provider = new QuestionTagProvider();
        $answer_provider = new AnswerTagProvider();

        $html = "";
        $sections = $form->getSections();
        foreach ($sections as $section) {
            if (count($sections) > 1) {
                $section_tag = $section_provider->getTagForSection($section);
                $html .= "<h2>$section_tag->html</h2>";
            }

            $i = 1;
            foreach ($section->getQuestions() as $question) {
                $question_tag  = $question_provider->getTagForQuestion($question);
                $answer_tag    = $answer_provider->getTagForQuestion($question);

                $html .= "<p><b>$i) $question_tag->html</b>: $answer_tag->html </p>";
                $i++;
            }
        }

        return new SimpleValueConfig($html);
    }

    #[Override]
    public function getDefaultConfig(Form $form): SimpleValueConfig
    {
        return new SimpleValueConfig('');
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }
}
