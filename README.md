MattyG's State Machine
======================

This is a fork of the Symfony Workflow component. It retains a lot of the core
ideas of the Symfony component, but makes a couple of rather important
adjustments. Most fundamentally, it has been transitioned away from a generic
workflow component into a proper state machine. This has been done to because
of the benefits of knowing that an entity is always only in a single state.
While some non-FSM workflows are still supported (by virtue of not being
explicitly checked for and disallowed in the code), they are discouraged.

You can find the original documentation for the Symfony component at the
following URL:

https://symfony.com/doc/current/components/workflow.html

It's still somewhat relevant, and should hopefully tide you over until I've had
a chance to write proper documentation myself!
