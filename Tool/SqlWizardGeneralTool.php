<?php


namespace Ling\SqlWizard\Tool;


/**
 * The SqlWizardGeneralTool class.
 */
class SqlWizardGeneralTool
{

    /**
     * Returns the first component of an underscore separated string, which we assume is a table prefix,
     * or null if the table name doesn't contain any underscore
     *
     * @param string $table
     * @return string|null
     */
    public static function getTablePrefix(string $table): ?string
    {
        $p = explode('_', $table, 2);
        if (count($p) > 1) {
            return array_shift($p);
        }
        return null;
    }


    /**
     * Removes the double-dash comments from the given content, and returns the stripped content.
     *
     * @param string $content
     * @return string
     */
    public static function removeDoubleDashComments(string $content): string
    {
        return preg_replace('!^--.*!m', '', $content);
    }
}