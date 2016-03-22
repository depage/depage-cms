<?php
/**
 * @file    htmlform.php
 * @brief   htmlform class and autoloader
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 *
 * @copyright this is the copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL2
 * @license http://www.spdx.org/licenses/MIT MIT License
 **/

// {{{ documentation
/**
 * @mainpage
 *
 * @intro
 * @image html icon_depage-forms.png
 * @htmlinclude main-intro.html
 * @endintro
 *
 * Usage
 * -----
 *
 * depage-forms will mainly be used through the @link Depage::HtmlForm::HtmlForm
 * HtmlForm-class@endlink. It is the main interface through which you can add
 * inputs, fieldsets and steps.
 *
 * You can find a list of available input-class in @link Depage::HtmlForm::Elements
 * elements@endlink.
 *
 * You can find a list of available parameters for inputs under @link
 * htmlformInputDefaults the input defaults@endlink.
 *
 * @subpage developer
 *
 * @htmlinclude main-extended.html
 **/

/**
 * @page usage Usage
 *
 **/

/**
 * @page developer Developer guide
 *
 * The idea behind this project is to combine comfortable form-generation and
 * modern browser functionality with maximum client coverage.
 *
 * Comfort
 * -------
 * - We abstract browser specifics from HTML-forms to provide a clean interface
 *   to web developers. All configuration is located in one place.
 *
 * Coverage
 * - To reach as many users as possible we have included several fallbacks for
 *   old browsers. Depage-forms mimics validation-behavior of modern browsers
 *   with JavaScript (client-side) or PHP (server-side).
 *
 * HTML5
 * -----
 * - We follow the HTML5 spec where it's sensible. The only clash so far is
 *   @link Depage::HtmlForm::Elements::Multiple::htmlInputAttributes checkbox
 *   validation @endlink .
 * - We aim to provide as much HTML5 functionality as possible.
 *
 * Customization
 * -------------
 * - Input-elements can be easily modified by overriding the included
 * element-classes.
 * - New element-classes are automatically integrated by the autoloader. (They
 * can be instantiated with @link Depage::HtmlForm::Abstracts::Container::__call
 * add @endlink (runtime generated methods))
 *
 * Developer Prerequisites
 * -----------------------
 * - PHP 5.3
 * - PHPUnit 3.5 (to run included unit tests)
 * - Doxygen 1.7.2 (to generate documentation)
 *
 * Coding style
 * ------------
 * Generally, follow PSR-0, PSR-1, PSR-2 coding standard (http://www.php-fig.org)
 *
 * Deployment
 * ----------
 * To generate a gzipped release of the library (includes examples):
 *
 *     $ make release
 *
 * To generate a gzipped release with the essentials for working environments:
 *
 *     $ make min
 *
 * Tests
 * -----
 * To run the unit tests:
 *
 *     $ make test
 *
 * Documentation
 * -------------
 * To generate documentation:
 *
 *     $ make doc
 **/
// }}}

// {{{ namespace
/**
 * @namespace depage
 * @brief depage cms
 *
 * @namespace Depage::HtmlForm
 * @brief htmlform class and autoloader
 *
 * @namespace Depage::HtmlForm::Abstracts
 * @brief Abstract element classes
 *
 * @namespace Depage::HtmlForm::Elements
 * @brief Classes for HTML input-elements
 *
 * All Classes in this namespace are HTML input-elements that can be added to
 * instances of the @link Depage::HtmlForm::HtmlForm HtmlForm-class@endlink,
 * but also to also to @link Depage::HtmlForm::Elements::Dieldset fieldsets@endlink
 * and @link Depage::HtmlForm::Elements::Step steps@endlink:
 *
 * These are the most used elements that are added to the form with the add-method:
 *
 * @code
    <?php
        $form = new Depage\HtmlForm\HtmlForm('simpleForm');

        // input[type=text]
        $input = $form->addText('input', array('label' => 'Normal Input'));

        // input[type=email]
        $form->addEmail('email', array('label' => 'Email Input'));

        // input[type=url]
        $form->addEmail('url', array('label' => 'URL Input'));

        // input[type=password]
        $form->addPassword('password', array('label' => 'Password Input'));

        // input[type=date]
        $form->addDate('date', array('label' => 'Date Input'));

        // input[type=number]
        $form->addNumber('number', array('label' => 'Number Input'));
    @endcode
 *
 * @namespace Depage::HtmlForm::Exceptions
 * @brief HtmlForm exceptions
 *
 * @namespace Depage::HtmlForm::Validators
 * @brief Validators for HTML input-elements
 **/
namespace Depage\HtmlForm;

// }}}

// {{{ autoloader
/**
 * @brief PHP autoloader
 *
 * Autoloads classes by namespace. (requires PHP >= 5.3)
 **/
function autoload($class)
{
    $class = str_replace('\\', '/', str_replace(__NAMESPACE__ . '\\', '', $class));
    $file = __DIR__ . '/' .  $class . '.php';

    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register(__NAMESPACE__ . '\autoload');
// }}}

/**
 * @brief main interface to users
 *
 * The class HtmlForm is the main tool of the htmlform library. It generates
 * input elements and container elements. It also contains the PHP session
 * handlers.
 *
 * When you use <em>depage-forms</em> this is probably the only class you will
 * instantiate directly.
 *
 * In general:
 *
 * @code
    <?php
        $form = new Depage\HtmlForm\HtmlForm('simpleForm');

        // add form fields
        $form->addText('username', array('label' => 'User name', 'required' => true));
        $form->addEmail('email', array('label' => 'Email address'));

        // process form
        $form->process();

        if ($form->validate()) {
            // do something with your valid data
            var_dump($form->getValues());
        } else {
            // Form was empty or data was not valid:
            // Display the form.
            echo ($form);
        }
    @endcode
 *
 * You can find a list of available input-class in @link Depage::HtmlForm::Elements
 * elements@endlink.
 *
 * See examples at
 *     - @link simple.php @endlink - a simple form
 **/
class HtmlForm extends Abstracts\Container
{
    // {{{ variables
    /**
     * @brief HTML form method attribute
     * */
    protected $method;
    /**
     * @brief HTML form action attribute
     **/
    protected $submitURL;
    /**
     * @brief Specifies where the user is redirected to, once the form-data is valid
     **/
    protected $successURL;
    /**
     * @brief Contains the submit button label of the form
     **/
    protected $label;
    /**
     * @brief Contains the additional class value of the form
     **/
    protected $class;
    /**
     * @brief Contains the name of the array in the PHP session, holding the form-data
     **/
    protected $sessionSlotName;
    /**
     * @brief PHP session handle
     **/
    protected $sessionSlot;
    /**
     * @brief Contains current step number
     **/
    private $currentStepId;
    /**
     * @brief Contains array of step object references
     **/
    private $steps = array();
    /**
     * @brief Time until session expiry (seconds)
     **/
    protected $ttl;
    /**
     * @brief Form validation result/status
     **/
    public $valid;
    /**
     * @brief List of internal fieldnames that are not part of the results
     **/
    protected $internalFields = array(
        'formIsValid',
        'formIsAutosaved',
        'formName',
        'formTimestamp',
        'formStep',
        'formFinalPost',
        'formCsrfToken',
    );
    /**
     * @brief Namespace strings for addible element classes
     **/
    protected $namespaces = array('\\Depage\\HtmlForm\\Elements');
    // }}}
    // {{{ __construct()
    /**
     * @brief   HtmlForm class constructor
     *
     * @param  string $name       form name
     * @param  array  $parameters form parameters, HTML attributes
     * @param  object $form       parent form object reference (not used in this case)
     * @return void
     **/
    public function __construct($name, $parameters = array(), $form = null)
    {
        $this->url = parse_url($_SERVER['REQUEST_URI']);

        parent::__construct($name, $parameters, $this);

        $this->currentStepId = isset($_GET['step']) ? $_GET['step'] : 0;

        $this->startSession();

        $this->valid = (isset($this->sessionSlot['formIsValid'])) ? $this->sessionSlot['formIsValid'] : null;

        // set CSRF Token
        if (!isset($this->sessionSlot['formCsrfToken'])) {
            $this->sessionSlot['formCsrfToken'] = $this->getNewCsrfToken();
        }

        if (!isset($this->sessionSlot['formFinalPost'])) {
            $this->sessionSlot['formFinalPost'] = false;
        }

        // create a hidden input to tell forms apart
        $this->addHidden('formName')->setValue($this->name);

        // create hidden input for submitted step
        $this->addHidden('formStep')->setValue($this->currentStepId);

        // create hidden input for CSRF token
        $this->addHidden('formCsrfToken')->setValue($this->sessionSlot['formCsrfToken']);

        $this->addChildElements();
    }
    // }}}
    // {{{ setDefaults()
    /**
     * @brief   Collects initial values across subclasses.
     *
     * The constructor loops through these and creates settable class
     * attributes at runtime. It's a compact mechanism for initialising
     * a lot of variables.
     *
     * @return void
     **/
    protected function setDefaults()
    {
        parent::setDefaults();

        $this->defaults['label']        = 'submit';
        $this->defaults['cancelLabel']  = null;
        $this->defaults['backLabel']    = null;
        $this->defaults['class']        = '';
        $this->defaults['method']       = 'post';
        // @todo adjust submit url for steps when used
        $this->defaults['submitURL']    = $_SERVER['REQUEST_URI'];
        $this->defaults['successURL']   = $_SERVER['REQUEST_URI'];
        $this->defaults['cancelURL']    = $_SERVER['REQUEST_URI'];
        $this->defaults['validator']    = null;
        $this->defaults['ttl']          = 30 * 60; // 30 minutes
        $this->defaults['jsValidation'] = 'blur';
        $this->defaults['jsAutosave']   = 'false';
    }
    // }}}

    // {{{ startSession()
    /**
     * @brief   Start session when there is no current session yet
     *
     * Starts a new session if there is no session, sets the session slot
     * and also calls the session expiry handler.
     *
     * @return void
     **/
    private function startSession()
    {
        // check if there's an open session
        if (!session_id()) {
            $sessionName = session_name();
            $sessionDomain = false;
            $sessionSecure = false;
            $sessionHttponly = true;

            session_set_cookie_params(
                $this->ttl,
                "/",
                $sessionDomain,
                $sessionSecure,
                $sessionHttponly
            );
            session_start();

            // Extend the expiration time upon page load
            if (isset($_COOKIE[$sessionName])) {
                setcookie(
                    $sessionName,
                    $_COOKIE[$sessionName],
                    time() + $this->ttl,
                    "/",
                    $sessionDomain,
                    $sessionSecure,
                    $sessionHttponly
                );
            }
        }
        $this->sessionSlotName  = 'htmlform-' . $this->name . '-data';
        $this->sessionSlot      =& $_SESSION[$this->sessionSlotName];

        $this->sessionExpiry();
    }
    // }}}
    // {{{ sessionExpiry()
    /**
     * @brief   Deletes session when it expires.
     *
     * checks if session lifetime exceeds ttl value and deletes it. Updates
     * timestamp.
     *
     * @return void
     **/
    private function sessionExpiry()
    {
        if (isset($this->ttl) && is_numeric($this->ttl)) {
            $timestamp = time();

            if (
                isset($this->sessionSlot['formTimestamp'])
                && ($timestamp - $this->sessionSlot['formTimestamp'] > $this->ttl)
            ) {
                $this->clearSession();
                $this->sessionSlot =& $_SESSION[$this->sessionSlotName];
            }

            $this->sessionSlot['formTimestamp'] = $timestamp;
        }
    }
    // }}}
    // {{{ isEmpty()
    /**
     * @brief   Returns wether form has been submitted before or not.
     *
     * @return bool session status
     **/
    public function isEmpty()
    {
        return !isset($this->sessionSlot['formName']);
    }
    // }}}

    // {{{ getNewCsrfToken()
    /**
     * @brief   Returns new XSRF token
     *
     * @return array element objects
     **/
    protected function getNewCsrfToken()
    {
        return base64_encode(openssl_random_pseudo_bytes(16));
    }
    // }}}

    // {{{ addElement()
    /**
     * @brief   Adds input or fieldset elements to htmlform.
     *
     * Calls parent class to generate an input element or a fieldset and add
     * it to its list of elements.
     *
     * @param  string $type       input type or fieldset
     * @param  string $name       name of the element
     * @param  array  $parameters element attributes: HTML attributes, validation parameters etc.
     * @return object $newElement element object
     **/
    protected function addElement($type, $name, $parameters)
    {
        $this->checkElementName($name);

        $newElement = parent::addElement($type, $name, $parameters);

        if ($newElement instanceof elements\step) { $this->steps[] = $newElement; }
        if ($newElement instanceof abstracts\input) { $this->updateInputValue($name); }

        return $newElement;
    }
    // }}}
    // {{{ checkElementName()
    /**
     * @brief   Checks for duplicate subelement names.
     *
     * Checks within the form if an input element or fieldset name is already
     * taken. If so, it throws an exception.
     *
     * @param  string $name name to check
     * @return void
     **/
    public function checkElementName($name)
    {
        foreach ($this->getElements(true) as $element) {
            if ($element->getName() === $name) {
                throw new Exceptions\DuplicateElementNameException("Element name \"{$name}\" already in use.");
            }
        }
    }
    // }}}
    // {{{ getCurrenElements()
    /**
     * @brief   Returns an array of input elements contained in the current step.
     *
     * @return array element objects
     **/
    private function getCurrentElements()
    {
        $currentElements = array();

        foreach ($this->elements as $element) {
            if ($element instanceof elements\fieldset) {
        if (
                    !($element instanceof elements\step)
                    || (isset($this->steps[$this->currentStepId]) && ($element == $this->steps[$this->currentStepId]))
        ) {
                    $currentElements = array_merge($currentElements, $element->getElements());
            }
            } else {
                $currentElements[] = $element;
        }
        }

        return $currentElements;
    }
    // }}}
    // {{{ registerNamespace
    /**
     * @brief   Stores element namespaces for adding
     *
     * @param  string $nameSpace namespace name
     * @return void
     **/
    public function registerNamespace($namespace)
    {
        $this->namespaces[] = $namespace;
    }
    // }}}
    // {{{ getNamespaces
    /**
     * @brief   Returns list of registered namespaces
     *
     * @return array
     **/
    public function getNamespaces()
    {
        return $this->namespaces;
    }
    // }}}

    // {{{ inCurrentStep()
    /**
     * @brief   Checks if the element named $name is in the current step.
     *
     * @param  string $name name of element
     * @return bool   says wether it's in the current step
     **/
    private function inCurrentStep($name)
    {
        return in_array($this->getElement($name), $this->getCurrentElements());
    }
    // }}}
    // {{{ setCurrentStep()
    /**
     * @brief   Validates step number of GET request.
     *
     * Validates step number of the GET request. If it's out of range it's
     * reset to the number of the first invalid step. (only to be used after
     * the form is completely created, because the step elements have to be
     * counted)
     *
     * @return void
     **/
    private function setCurrentStep()
    {
        if (!is_numeric($this->currentStepId)
            || ($this->currentStepId > count($this->steps) - 1)
            || ($this->currentStepId < 0)
        ) {
            $this->currentStepId = $this->getFirstInvalidStep();
        }
    }
    // }}}
    // {{{ getSteps()
    /**
     * @brief   Returns an array of steps
     *
     * @return array step objects
     **/
    public function getSteps()
    {
        return $this->steps;
    }
    // }}}
    // {{{ getCurrentStepId()
    /**
     * @brief   Returns the current step id
     *
     * @return int current step
     **/
    public function getCurrentStepId()
    {
        return $this->currentStepId;
    }
    // }}}
    // {{{ getFirstInvalidStep()
    /**
     * @brief   Returns first step that didn't pass validation.
     *
     * Checks steps consecutively and returns the number of the first one that
     * isn't valid (steps need to be submitted at least once to count as valid).
     * Form must have been validated before calling this method.
     *
     * @return int $stepNumber number of first invalid step
     **/
    public function getFirstInvalidStep()
    {
        if (count($this->steps) > 0) {
            foreach ($this->steps as $stepNumber => $step) {
                if (!$step->validate()) {
                    return $stepNumber;
                }
            }
            /**
             * If there aren't any invalid steps there must be a fieldset
             * directly attached to the form that's invalid. In this case we
             * don't want to jump back to the first step. Hence, this little
             * hack.
             **/
            return count($this->steps) - 1;
            } else {
            return 0;
            }
        }
    // }}}
    // {{{ buildUrlQuery()
    /**
     * @brief   Adding step parameter to already existing query
     *
     * @return  new query
     **/
    public function buildUrlQuery($args = array())
    {
        $query = '';
        $queryParts = array();

        if (isset($this->url['query']) && $this->url['query'] != "") {
            //decoding query string
            $query = html_entity_decode($this->url['query']);

            //parsing the query into an array
            parse_str($query, $queryParts);
    }

        foreach ($args as $name => $value) {
            if ($value != "") {
                $queryParts[$name] = $value;
            } elseif (isset($queryParts[$name])) {
                unset($queryParts[$name]);
            }
        }

        // build the query again
        $query = http_build_query($queryParts);

        if ($query != "") {
            $query = "?" . $query;
        }

        return $query;
    }
    // }}}

    // {{{ updateInputValue()
    /**
     * @brief   Updates the value of an associated input element
     *
     * Sets the input elements' value. If there is post-data - we'll use that
     * to update the value of the input element and the session. If not - we
     * take the value that's already in the session. If the value is neither in
     * the session nor in the post-data - nothing happens.
     *
     * @param  string $name name of the input element
     * @return void
     **/
    public function updateInputValue($name)
    {
        $element = $this->getElement($name);
        // if it's a post, take the value from there and save it to the session
        if (
            isset($_POST['formName']) && ($_POST['formName'] === $this->name)
            && $this->inCurrentStep($name)
            && isset($_POST['formCsrfToken']) && $_POST['formCsrfToken'] === $this->sessionSlot['formCsrfToken']
        ) {
            if ($this->getElement($name) instanceof elements\file) {
                // handle uploaded file
                $oldValue = isset($this->sessionSlot[$name]) ? $this->sessionSlot[$name] : null;
                $this->sessionSlot[$name] = $element->handleUploadedFiles($oldValue);
            } else if (!$element->getDisabled()) {
                // save value
                $value = isset($_POST[$name]) ? $_POST[$name] : null;
                $this->sessionSlot[$name] = $element->setValue($value);
            } else if (!isset($this->sessionSlot[$name])) {
                // set default value for disabled elements
                $this->sessionSlot[$name] = $element->setValue($element->getDefaultValue());
    }
        }
        // if it's not a post, try to get the value from the session
        else if (isset($this->sessionSlot[$name])) {
            $element->setValue($this->sessionSlot[$name]);
        }
    }
    // }}}

    // {{{ populate()
    /**
     * @brief   Fills subelement values.
     *
     * Allows to manually populate the forms' input elements with values by
     * parsing an array of name-value pairs.
     *
     * @param  array $data input element names (key) and values (value)
     * @return void
     **/
    public function populate($data = array())
    {
        foreach ($this->getElements() as $element) {
            $name = $element->name;
            if (!in_array($name, $this->internalFields)) {
                if (is_array($data) && isset($data[$name])) {
                    $value = $data[$name];
                } else if (is_object($data) && isset($data->$name)) {
                    $value = $data->$name;
                }

                if (isset($value)) {
                    $element->setDefaultValue($value);
                    if ($element->getDisabled() && !isset($this->sessionSlot[$name])) {
                        $this->sessionSlot[$name] = $value;
            }
        }

                unset($value);
        }
        }
    }
    // }}}
    // {{{ process()
    /**
     * @brief   Calls form validation and handles redirects.
     *
     * Implememts the Post/Redirect/Get strategy. Redirects to success Address
     * on succesful validation otherwise redirects to first invalid step or
     * back to form.
     *
     * @return void
     *
     * @see     validate()
     **/
   public function process()
   {
        $this->setCurrentStep();
        // if there's post-data from this form
        if (isset($_POST['formName']) && ($_POST['formName'] === $this->name)) {
            // save in session if submission was from last step
            $this->sessionSlot['formFinalPost'] = count($this->steps) == 0 || $_POST['formStep'] + 1 == count($this->steps);

            if (!empty($this->cancelLabel) && isset($_POST['formSubmit']) && $_POST['formSubmit'] === $this->cancelLabel) {
                // cancel button was pressed
                $this->clearSession();
                $this->redirect($this->cancelURL);
            } elseif (!empty($this->backLabel) && isset($_POST['formSubmit']) && $_POST['formSubmit'] === $this->backLabel) {
                // back button was pressed
                $this->sessionSlot['formFinalPost'] = false;
                $prevStep = $this->currentStepId - 1;
                if ($prevStep < 0) {
                    $prevStep = 0;
                }
                $urlStepParameter = ($prevStep <= 0) ? $this->buildUrlQuery(array('step' => '')) : $this->buildUrlQuery(array('step' => $prevStep));
                $this->redirect($this->url['path'] . $urlStepParameter);
            } elseif ($this->validate()) {
                // form was successfully submitted
                $this->redirect($this->successURL);
            } else {
                // goto to next step or display first invalid step
                $nextStep = $this->currentStepId + 1;
                $firstInvalidStep = $this->getFirstInvalidStep();
                if ($nextStep > $firstInvalidStep) {
                    $nextStep = $firstInvalidStep;
                }
                if ($nextStep > count($this->steps)) {
                    $nextStep = count($this->steps) - 1;
                }
                $urlStepParameter = ($nextStep == 0) ? $this->buildUrlQuery(array('step' => '')) : $this->buildUrlQuery(array('step' => $nextStep));
                $this->redirect($this->url['path'] . $urlStepParameter);
            }
        }
    }
    // }}}
    // {{{ validate()
    /**
     * @brief   Validates the forms subelements.
     *
     * Form validation - validates form elements returns validation result and
     * writes it to session. Also calls custom validator if available.
     *
     * @return bool validation result
     *
     * @see     process()
     **/
    public function validate()
    {
        // onValidate hook for custom required/validation rules
        $this->valid = $this->onValidate();

        $this->valid = $this->valid && $this->validateAutosave();

        if ($this->valid && !is_null($this->validator)) {
            if (is_callable($this->validator)) {
                $this->valid = call_user_func($this->validator, $this, $this->getValues());
            } else {
                throw new exceptions\validatorNotCallable("The validator paramater must be callable");
            }
        }
        $this->valid = $this->valid && $this->sessionSlot['formFinalPost'];

        // save validation-state in session
        $this->sessionSlot['formIsValid'] = $this->valid;

        return $this->valid;
    }
    // }}}
    // {{{ onValidate()
    /**
     * @brief   Validation hook
     *
     * Can be overridden with custom validation rules, field-required rules etc.
     *
     * @return void
     *
     * @see     validate()
     **/
    protected function onValidate()
    {
        return true;
    }
    // }}}
    // validateAutosave() {{{
    /**
     * If the form is autosaving the validation property is defaulted to false.
     *
     * This function returns the actual state of input validation.
     * It can therefore be used to test autosave fields are correct without forcing
     * the form save.
     *
     * @return bool $partValid - whether the autosave postback data is valid
     */
    public function validateAutosave()
    {
        parent::validate();

        $partValid = $this->valid;

        if (isset($_POST['formName']) && $_POST['formName'] == $this->name) {
            if (isset($_POST['formCsrfToken'])) {
                $hasCorrectToken = $_POST['formCsrfToken'] === $this->sessionSlot['formCsrfToken'];
                $this->valid = $this->valid && $hasCorrectToken;
                $partValid = $this->valid;

                if (!$hasCorrectToken) {
                    $this->log("HtmlForm: Requst invalid because of incorrect CsrfToken in form {$this->name}");
                }
            }

            // save data in session when autosaving but don't validate successfully
            if ((isset($_POST['formAutosave']) && $_POST['formAutosave'] === "true")
                    || (isset($this->sessionSlot['formIsAutosaved'])
                        && $this->sessionSlot['formIsAutosaved'] === true)) {
                $this->valid = false;
            }

            // save whether form was autosaved the last time
            $this->sessionSlot['formIsAutosaved'] = isset($_POST['formAutosave']) && $_POST['formAutosave'] === "true";
        }

        return $partValid;
    }
    // }}}
    // {{{ getValues()
    /**
     * @brief   Gets form-data from current PHP session.
     *
     * @return array form-data
     **/
    public function getValues()
    {
        if (isset($this->sessionSlot)) {
            // remove internal attributes from values
            return array_diff_key($this->sessionSlot, array_fill_keys($this->internalFields, ''));
        } else {
            return null;
        }
    }
    // }}}
    // {{{ getValuesWithLabel()
    /**
     * @brief   Gets form-data from current PHP session but also contain elemnt labels.
     *
     * @return array form-data with labels
     **/
    public function getValuesWithLabel()
    {
        //get values first
        $values = $this->getValues();
        $valuesWithLabel = array();
        if (isset($values)) {
            foreach ($values as $element => $value) {
                $elem = $this->getElement($element);

                if ($elem) $valuesWithLabel[$element] = array(
                    "value" => $value,
                    "label" => $elem->getLabel()
                );
            }

            return $valuesWithLabel;
        } else {
            return null;
        }
    }
    // }}}
    // {{{ redirect()
    /**
     * @brief Redirects Browser to a different URL.
     *
     * @param string $url url to redirect to
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
        die( "Tried to redirect you to <a href=\"$url\">$url</a>");
            }
    // }}}
    // {{{ clearSession()
    /**
     * @brief   Deletes the current forms' PHP session data.
     *
     * @param bool $clearCsrfToken whether to clear complete form or just the data values and to keep csrf-token.
     *
     * @return void
     **/
    public function clearSession($clearCsrfToken = true)
    {
        if ($clearCsrfToken) {
            // clear everything
        $this->clearValue();

        unset($_SESSION[$this->sessionSlotName]);
        unset($this->sessionSlot);
        } else {
            // clear everything except internal fields
            foreach ($this->getElements(true) as $element) {
                if (!$element->getDisabled() && !in_array($element->name, $this->internalFields)) {
                    unset($this->sessionSlot[$element->name]);
    }
            }
        }
    }
    // }}}

    // {{{ htmlDataAttributes()
    /**
     * @brief   Returns dataAttr escaped as attribute string
     **/
    protected function htmlDataAttributes()
    {
        $this->dataAttr['jsvalidation'] = $this->jsValidation;
        $this->dataAttr['jsautosave'] = $this->jsAutosave === true ? "true" : $this->jsAutosave;

        return parent::htmlDataAttributes();
        }
    // }}}
    // {{{ __toString()
    /**
     * @brief   Renders form to HTML.
     *
     * Renders the htmlform object to HTML code. If the form contains elements
     * it calls their rendering methods.
     *
     * @return string HTML code
     **/
    public function __toString()
    {
        $renderedElements   = '';
        $label              = $this->htmlLabel();
        $cancellabel        = $this->htmlCancelLabel();
        $backlabel          = $this->htmlBackLabel();
        $class              = $this->htmlClass();
        $method             = $this->htmlMethod();
        $submitURL          = $this->htmlSubmitURL();
        $dataAttr           = $this->htmlDataAttributes();

        foreach ($this->elementsAndHtml as $element) {
            // leave out inactive step elements
            if (!($element instanceof elements\step)
                || (isset($this->steps[$this->currentStepId]) && $this->steps[$this->currentStepId] == $element)
            ) {
                $renderedElements .= $element;
    }
        }

        if (!is_null($this->cancelLabel)) {
            $cancel = "<p id=\"{$this->name}-cancel\" class=\"cancel\"><input type=\"submit\" name=\"formSubmit\" value=\"{$cancellabel}\"></p>\n";
        } else {
            $cancel = "";
        }
        if (!is_null($this->backLabel) && $this->currentStepId > 0) {
            $back = "<p id=\"{$this->name}-back\" class=\"back\"><input type=\"submit\" name=\"formSubmit\" value=\"{$backlabel}\"></p>\n";
        } else {
            $back = "";
        }


        return "<form id=\"{$this->name}\" name=\"{$this->name}\" class=\"depage-form {$class}\" method=\"{$method}\" action=\"{$submitURL}\"{$dataAttr} enctype=\"multipart/form-data\">" . "\n" .
            $renderedElements .
            "<p id=\"{$this->name}-submit\" class=\"submit\"><input type=\"submit\" name=\"formSubmit\" value=\"{$label}\"></p>" . "\n" .
            $cancel .
            $back .
        "</form>";
    }
    // }}}
}

// {{{ group pages
/**
 * @defgroup htmlformInputDefaults List of default options for inputs
 *
 * These are general defaults for input elements that can be overridden on
 * a per input basis:
 *
 * @code
    <?php
        $form = new Depage\HtmlForm\HtmlForm('simpleForm');

        $input = $form->addText('input', array(
            'autocapitalize' => true,
            'autocomplete' => true,
            'autocorrect' => true,
            'autofocus' => true,
            'disabled' => false,
            'errorMessage' => "There was a problem with your input",
            'label' => "Normal Input",
            'marker' => "*",
            'required' => true,
            'class' => "my-class",
            'helpMessage' => "addition help message",
            'helpMessageHtml' => "addition <b>help message</b>",
        );

    @endcode
 **/
// }}}

// {{{ example page links
/**
 * @example elements.php
 * @brief   Various element examples
 *
 * Demonstrates the use of all element types that are fully implemented.
 * Includes examples of element specific options.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/elements.php"></iframe>@endhtmlonly
 **/

/**
 * @example js-validation.php
 * @brief   Client-side JavaScript validation
 *
 * Demonstrates client-side validation. Fields are validated when they lose
 * focus. The regular expression has to match the entire string (as in HTML5
 * patterns). Contrary to PHP preg_match() "/[a-z]/" does not match "aaa".
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/js-validation.php"></iframe>@endhtmlonly
 **/

/**
 * @example lists.php
 * @brief   Option list examples
 *
 * Multiple, single and text input elements have option lists. Here are some
 * more detailed examples.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/lists.php"></iframe>@endhtmlonly
 **/

/**
 * @example populate.php
 * @brief   Populate form example
 *
 * The HtmlForm->populate method provides a comfortable way to fill in
 * default values before displaying the form. It's more convenient than setting
 * the 'defaultValue' parameter for each input element individually.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/populate.php"></iframe>@endhtmlonly
 **/

/**
 * @example reallife.php
 * @brief   (almost) real life depage-forms example
 *
 * Contains a common personal information form. Some elements are set required
 * and the username has a minimum length.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/reallife.php"></iframe>@endhtmlonly
 **/

/**
 * @example redirect.php
 * @brief   Redirect to success page example
 *
 * Once the form is validated, it can also be redirected to another URL. This
 * can be done by setting the 'successURL' parameter in the form.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/redirect.php"></iframe>@endhtmlonly
 *
 * @include redirect-success.php
 **/

/**
 * @example session-timeout.php
 * @brief   Form expiry example
 *
 * One can lower the lifetime of the forms session by setting the 'ttl' value.
 * Setting this higher than the PHP session length (default around 30min) has
 * no effect.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/session-timeout.php"></iframe>@endhtmlonly
 **/

/**
 * @example simple.php
 * @brief   Simple form example
 *
 * Contains text and email input elements and demonstrates form processing and
 * validation. In this simple case none of the elements are set required, so an
 * empty form would also be valid.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/simple.php"></iframe>@endhtmlonly
 **/

/**
 * @example steps.php
 * @brief   Steps example
 *
 * This demonstrates dividing the form into separate pages using step
 * container elements. As usual the form itself can only be valid when all
 * its input elements are valid.
 * It is also possible to attach input elements directly to the form (outside
 * any step container). These elements are visible on every step.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/steps.php"></iframe>@endhtmlonly
 **/

/**
 * @example closure.php
 * @brief   closure validation example
 *
 * It's also possible to validate a form element with a closure function
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/closure.php"></iframe>@endhtmlonly
 **/

/**
 * @example subclass.php
 * @brief   Form subclass example
 *
 * It's also possible to override the HtmlForm class and add elements in the
 * constructor. This way we can build reusable form classes or add custom rules
 * to the form validation.
 *
 * @htmlonly<iframe class="example" seamless="seamless" src="../examples/subclass.php"></iframe>@endhtmlonly
 **/
// }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
