<?php

namespace Codeception\Module;

use Codeception\Module;
use Codeception\Util\Locator;
use Ixis\Codeception\Util\BrowserTrait;

class CodeceptionUtilities extends Module
{
    use BrowserTrait;

    /**
     * Looks for a link in a particular CSS or XPath selector.
     *
     * @param string $text
     *   Text to look for.
     * @param string $link
     *   URL the text should link to.
     * @param string $cssOrXpath
     *   The selector in which to look for the link.
     */
    public function seeLinkInSelector($text, $link, $cssOrXpath)
    {
        $this->getBrowserModule()->see($text, $this->getLinkSelector($link, $cssOrXpath));
    }

    /**
     * Checks that a link does not appear in a particular CSS or XPath selector.
     *
     * @param string $text
     *   Text to ensure doesn't exist.
     * @param string $link
     *   URL the text should not link to.
     * @param string $cssOrXpath
     *   The selector in which to look for the lack of link.
     */
    public function dontSeeLinkInSelector($text, $link, $cssOrXpath)
    {
        $this->getBrowserModule()->dontSee($text, $this->getLinkSelector($link, $cssOrXpath));
    }

    /**
     * Helper method to calculate the correct link selector.
     *
     * @param string $link
     * @param string $cssOrXpath
     *
     * @return string
     */
    protected function getLinkSelector($link, $cssOrXpath)
    {
        if ($link) {
            if (Locator::isCSS($cssOrXpath)) {
                $link_selector = sprintf("%s a[href*='%s']", $cssOrXpath, $link);
            } else {
                $link_selector = sprintf("%s//a[contains(@href,'%s')]", $cssOrXpath, $link);
            }
        } else {
            if (Locator::isCSS($cssOrXpath)) {
                $link_selector = sprintf("%s a", $cssOrXpath);
            } else {
                $link_selector = sprintf("%s//a", $cssOrXpath);
            }
        }

        return $link_selector;
    }

    /**
     * See element has been applied a style.
     *
     * Allows you to check a single element has a css style assigned.
     * e.g. you can check that ".icon-r" class is floated right.
     *
     * @param string $selector
     *   A CSS (only) selector to identify the element.
     * @param string $style
     *   The style name e.g. font-weight, float
     * @param string $value
     *   The value to check e.g. bold, right
     * @param string $pseudo
     *   The pseudo selector to retrieve the style for e.g. ::before, :hover
     */
    public function seeElementHasStyle($selector, $style, $value, $pseudo = null)
    {
        $this->assert($this->proceedSeeElementHasStyle($selector, $style, $value, $pseudo));
    }

    /**
     * See element has not been applied a style.
     *
     * @param string $selector
     *   A CSS (only) selector to identify the element.
     * @param string $style
     *   The style name e.g. font-weight, float
     * @param string $value
     *   The value to check e.g. bold, right
     * @param string $pseudo
     *   The pseudo selector to retrieve the style for e.g. ::before, :hover
     */
    public function dontSeeElementHasStyle($selector, $style, $value, $pseudo = null)
    {
        $this->assertNot($this->proceedSeeElementHasStyle($selector, $style, $value, $pseudo));
    }

    /**
     * Helper method for checking an element has a css style applied.
     *
     * @param string $selector
     *   A CSS (only) selector to identify the element.
     * @param string $style
     *   The style name e.g. font-weight, float
     * @param string $value
     *   The value to check e.g. bold, right
     * @param string $pseudo
     *   The pseudo selector to retrieve the style for e.g. ::before, :hover
     *
     * @return array
     *
     * @throws \LogicException
     *   If attempting to call function when WebDriver not in use.
     */
    protected function proceedSeeElementHasStyle($selector, $style, $value, $pseudo = null)
    {
        $computedvalue = $this->grabElementStyle($selector, $style, $pseudo);

        return array("Equals", $value, $computedvalue);
    }

    /**
     * Get a style value from an element.
     *
     * @param string $selector
     *   A CSS (only) selector to identify the element.
     * @param string $style
     *   The style name e.g. font-weight, float
     * @param string $pseudo
     *   The pseudo selector to retrieve the style for e.g. ::before, :hover
     *
     * @return mixed
     *
     * @throws \LogicException
     *   If attempting to call function when WebDriver not in use.
     */
    public function grabElementStyle($selector, $style, $pseudo = null)
    {
        if ($this->getBrowserModuleName() !== 'WebDriver') {
            throw new \LogicException("Computed styles only available for inspection when using WebDriver");
        }

        $js = sprintf(
          "return window.getComputedStyle(document.querySelector('%s')%s)['%s']",
          $selector,
          $pseudo ? ", '$pseudo'" : "",
          $style
        );

        return $this->getModule("WebDriver")->executeJs($js);
    }

    /**
     * Helper function for seeRegexInSource() and dontSeeRegexInSource().
     *
     * Switch behaviour based on whether we're using PhpBrowser or WebDriver because they both handle acquisition of
     * page content slightly differently.
     *
     * @param string $string
     *   The regular expression to check for.
     *
     * @return bool
     *   Returns true if string found.
     */
    protected function proceedSeeRegexInSource($string)
    {
        $moduleName = $this->getBrowserModuleName();

        switch ($moduleName) {
            case 'PhpBrowser':
            default:
                $session = $this->getModule('PhpBrowser')->client;
                $content = $session->getResponse()->getContent();
                break;

            case 'WebDriver':
                /** @var WebDriver $module */
                $module = $this->getModule($moduleName);
                $content = $module->webDriver->getPageSource();
                break;
        }

        return array("True", preg_match($string, $content) === 1);
    }

    /**
     * Perform a simple preg_match() to check for regex $string in a page's
     * source.
     *
     * @param $string
     *   The regex string to check for.
     */
    public function seeRegexInSource($string)
    {
        $this->assert($this->proceedSeeRegexInSource($string));
    }

    /**
     * Perform a simple preg_match to check that regex $string does not
     * exist in a page's source.
     *
     * @param $string
     *   The regex string to check for.
     */
    public function dontSeeRegexInSource($string)
    {
        $this->assertNot($this->proceedSeeRegexInSource($string));
    }
}
