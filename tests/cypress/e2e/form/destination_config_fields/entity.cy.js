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

describe('Entity configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        // Create form with a single "entity" question
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');
        cy.findByRole('button', { 'name': "Add a new question" }).click();
        cy.focused().type("My entity question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');

        cy.getDropdownByLabelText('Select an itemtype').selectDropdownValue('Entities');

        cy.findByRole('button', { 'name': 'Save' }).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Items to create" }).click();
        cy.findByRole('button', { 'name': "Add ticket" }).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully added');
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', { 'name': "Entity configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Entity').as("entity_dropdown");

        // Default value
        cy.get('@entity_dropdown').should(
            'have.text',
            'Answer to last "Entity" item question'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select an entity...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select a question...').should('not.exist');

        // Switch to "From form"
        cy.get('@entity_dropdown').selectDropdownValue('From form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.get('@entity_dropdown').should('have.text', 'From form');

        // Switch to "From first requester"
        cy.get('@entity_dropdown').selectDropdownValue('From first requester');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.get('@entity_dropdown').should('have.text', 'From first requester');

        // Switch to "From user"
        cy.get('@entity_dropdown').selectDropdownValue('From user');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.get('@entity_dropdown').should('have.text', 'From user');

        // Switch to "Specific entity"
        cy.get('@entity_dropdown').selectDropdownValue('Specific entity');
        cy.get('@config').getDropdownByLabelText('Select an entity...').as('specific_entity_dropdown');
        cy.get('@specific_entity_dropdown').selectDropdownValue('»E2ETestEntity');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.get('@entity_dropdown').should('have.text', 'Specific entity');
        cy.get('@specific_entity_dropdown').should('have.text', 'Root entity > E2ETestEntity');

        // Switch to "Answer from a specific question"
        cy.get('@entity_dropdown').selectDropdownValue('Answer from a specific question');
        cy.get('@config').getDropdownByLabelText('Select a question...').as('specific_answer_type_dropdown');
        cy.get('@specific_answer_type_dropdown').selectDropdownValue('My entity question');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.get('@entity_dropdown').should('have.text', 'Answer from a specific question');
        cy.get('@specific_answer_type_dropdown').should('have.text', 'My entity question');

        // Switch to "Answer to last "Entity" item question"
        cy.get('@entity_dropdown').selectDropdownValue('Answer to last "Entity" item question');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.get('@entity_dropdown').should('have.text', 'Answer to last "Entity" item question');
    });

    it('can create ticket using default configuration', () => {
        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('Entity', {
                name: "E2ETestEntityForFormDestinationField-" + form_id,
                entities_id: 1, // subentity of E2ETestEntity
            });
        });

        cy.findByRole('link', { 'name': "User menu" }).click();
        cy.findByRole('link', { 'name': "Select the desired entity" }).click();
        cy.findByRole('treegrid', { 'name': "Entity tree" }).as('entities');
        cy.get('@entities').findByRole('link', { 'name': "+ sub-entities" }).click();

        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Fill form
        cy.get('@form_id').then((form_id) => {
            cy.getDropdownByLabelText("Select an item").selectDropdownValue("»E2ETestEntityForFormDestinationField-" + form_id);
        });
        cy.findByRole('button', { 'name': 'Send form' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.get('@form_id').then((form_id) => {
            cy.findByRole('region', { 'name': 'Ticket' }).findAllByRole('link', 'Root entity > E2ETestEntity > E2ETestEntityForFormDestinationField-' + form_id).should('exist');
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
