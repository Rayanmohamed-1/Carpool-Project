<?php

// these are the functions we are testing
// they match the validation logic used in register.php

// checks if the email is a cardiff met email
function isCardiffMetEmail($email) {
    if (str_ends_with($email, '@cardiffmet.ac.uk')) {
        return true;
    } elseif (str_ends_with($email, '@outlook.uwicac.ac.uk')) {
        return true;
    } else {
        return false;
    }
}

// checks password is long enough
function isValidPassword($password) {
    if (strlen($password) >= 8) {
        return true;
    }
    return false;
}

// checks that name email and password are not empty
function hasRequiredFields($name, $email, $password) {
    if (empty($name) || empty($email) || empty($password)) {
        return false;
    }
    return true;
}

// run all the tests and print results
function runTests() {
    $passed = 0;
    $failed = 0;

    echo "Running registration validation tests...\n\n";

    // test 1 - valid cardiff met email should be accepted
    if (isCardiffMetEmail('student@cardiffmet.ac.uk') === true) {
        echo "PASS - valid Cardiff Met email accepted\n";
        $passed++;
    } else {
        echo "FAIL - valid Cardiff Met email was rejected\n";
        $failed++;
    }

    // test 2 - gmail should not be allowed
    if (isCardiffMetEmail('student@gmail.com') === false) {
        echo "PASS - Gmail address correctly rejected\n";
        $passed++;
    } else {
        echo "FAIL - Gmail address was not rejected\n";
        $failed++;
    }

    // test 3 - empty email should not be allowed
    if (isCardiffMetEmail('') === false) {
        echo "PASS - empty email correctly rejected\n";
        $passed++;
    } else {
        echo "FAIL - empty email was not rejected\n";
        $failed++;
    }

    // test 4 - short password should fail
    if (isValidPassword('short') === false) {
        echo "PASS - short password correctly rejected\n";
        $passed++;
    } else {
        echo "FAIL - short password was not rejected\n";
        $failed++;
    }

    // test 5 - longer password should pass
    if (isValidPassword('longpassword123') === true) {
        echo "PASS - valid password accepted\n";
        $passed++;
    } else {
        echo "FAIL - valid password was rejected\n";
        $failed++;
    }

    // test 6 - all empty fields should fail
    if (hasRequiredFields('', '', '') === false) {
        echo "PASS - empty fields correctly rejected\n";
        $passed++;
    } else {
        echo "FAIL - empty fields were not rejected\n";
        $failed++;
    }

    // test 7 - outlook cardiff met email should also be accepted
    if (isCardiffMetEmail('student@outlook.uwicac.ac.uk') === true) {
        echo "PASS - outlook Cardiff Met email accepted\n";
        $passed++;
    } else {
        echo "FAIL - outlook Cardiff Met email was rejected\n";
        $failed++;
    }

    // test 8 - all fields filled in should pass
    if (hasRequiredFields('John Smith', 'john@cardiffmet.ac.uk', 'password123') === true) {
        echo "PASS - all required fields present\n";
        $passed++;
    } else {
        echo "FAIL - required fields check failed\n";
        $failed++;
    }

    // print final results
    echo "\n--------------------------\n";
    echo "$passed passed, $failed failed\n";

    if ($failed === 0) {
        echo "All tests passed!\n";
    } else {
        echo "Some tests failed - check the output above\n";
    }
}

// run the tests
runTests();
?>