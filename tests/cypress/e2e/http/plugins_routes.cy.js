import {console_command} from '../../console_command';

describe('Plugin routes', () => {
    beforeEach(() => {
        cy.login();
    });

    it('returns 404 when plugin is not active', () => {
        cy.exec(`${console_command} plugin:deactivate --env=testing tester`, {failOnNonZeroExit: false});

        cy.visit(`/plugins/tester/plugin-test`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(404);
        });
    });

    it('returns 200 when plugin is active', () => {
        cy.exec(`${console_command} plugin:install --env=testing tester --force -u glpi`, {failOnNonZeroExit: false})
            .then(res => {
                cy.log(`Install response status: ${res.code}\n\nSTDERR: ${res.stderr}\n\nSTDOUT: ${res.stdout}`);
            });

        cy.exec(`${console_command} plugin:activate --env=testing tester`, {failOnNonZeroExit: false})
            .then(res => {
                cy.log(`Activate response status: ${res.code}\n\nSTDERR: ${res.stderr}\n\nSTDOUT: ${res.stdout}`);
            });

        cy.visit(`/plugins/tester/plugin-test`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(200);
        });

        cy.findByText('It works!').should('exist');
    });
});
