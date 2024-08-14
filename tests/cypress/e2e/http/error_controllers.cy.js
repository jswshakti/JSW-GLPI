import {console_command} from '../../console_command';
import path from 'path';

describe('Error controller', () => {
    const front_path = '../front';
    const fixtures_path = 'fixtures/example_error_files';

    before(() => {
        cy.exec(`${console_command} plugin:install --env=testing tester --force -u glpi`, {failOnNonZeroExit: false})
        cy.exec(`${console_command} plugin:activate --env=testing tester`, {failOnNonZeroExit: false})

        cy.exec(`mkdir -p "${front_path}/testing/"`);
        cy.exec(`cp ${fixtures_path}/* "${front_path}/testing/"`);
    });

    after(() => {
        cy.exec(`${console_command} plugin:deactivate --env=testing tester`, {failOnNonZeroExit: false})

        cy.exec(`rm -rf "${front_path}/testing"`);
    });

    beforeEach(() => {
        cy.login();
    });

    it('displays warning message', () => {
        cy.visit(`/front/testing/error_warning.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(200);
        });

        cy.findByRole('main').findByText('Example page to trigger a PHP Warning error.').should('exist');
    });

    it('displays triggered error message', () => {
        cy.visit(`/front/testing/error_trigger.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(500);
        });

        cy.findByRole('alert').should('contain.text', 'User Error: Error triggered');
    });

    it('displays thrown exception message', () => {
        cy.visit(`/front/testing/error_exception.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(500);
        });

        cy.findByRole('alert').should('contain.text', 'Exception triggered');
    });

    it('displays parse error message', () => {
        cy.visit(`/front/testing/error_parse.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(500);
        });

        cy.findByRole('alert').should('contain.text', 'syntax error, unexpected end of file');
    });
});
