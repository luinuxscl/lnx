<?php

use App\Models\User;
use App\Support\Dashboard\DashboardRegistry;
use App\Support\Dashboard\DashboardSection;
use App\Support\Dashboard\Widget;
use App\Support\Dashboard\WidgetData;
use App\Support\Dashboard\WidgetType;

abstract class DashboardFixtureBase extends Widget
{
    public function section(): DashboardSection
    {
        return DashboardSection::Stats;
    }

    public function type(): WidgetType
    {
        return WidgetType::Text;
    }

    public function resolve(User $actor): ?WidgetData
    {
        return null;
    }
}

class DashboardUnprefixedWidget extends DashboardFixtureBase
{
    public function id(): string
    {
        return 'lnx.fixture';
    }
}

class DashboardSameIdOneWidget extends DashboardFixtureBase
{
    public function id(): string
    {
        return 'test.same';
    }
}

class DashboardSameIdTwoWidget extends DashboardSameIdOneWidget {}

class DashboardFirstWidget extends DashboardFixtureBase
{
    public function id(): string
    {
        return 'test.first';
    }
}

class DashboardSecondWidget extends DashboardFixtureBase
{
    public function id(): string
    {
        return 'test.second';
    }
}

class DashboardOutOfRangeWidget extends DashboardFixtureBase
{
    public function id(): string
    {
        return 'test.out_of_range';
    }

    public function span(): int
    {
        return 13;
    }
}

it('rejects a widget id that is not prefixed by the package source', function () {
    app(DashboardRegistry::class)->register('test', [DashboardUnprefixedWidget::class]);
})->throws(InvalidArgumentException::class);

it('rejects a duplicate widget id inside one declaration', function () {
    app(DashboardRegistry::class)->register('test', [
        DashboardSameIdOneWidget::class,
        DashboardSameIdTwoWidget::class,
    ]);
})->throws(InvalidArgumentException::class);

it('rejects a span outside the twelve column grid', function () {
    app(DashboardRegistry::class)->register('test', [DashboardOutOfRangeWidget::class]);
})->throws(InvalidArgumentException::class);

it('rejects a class that does not implement the contract', function () {
    app(DashboardRegistry::class)->register('test', [stdClass::class]);
})->throws(InvalidArgumentException::class);

it('replaces the widgets of a source when it is registered twice', function () {
    $registry = app(DashboardRegistry::class);

    $registry->register('test', [DashboardFirstWidget::class]);
    $registry->register('test', [DashboardSecondWidget::class]);

    $ids = array_map(static fn ($widget) => $widget->id(), $registry->all());

    expect($ids)->toContain('test.second')
        ->and($ids)->not->toContain('test.first');
});

it('lists the widgets registered by the kit', function () {
    $ids = array_map(
        static fn ($widget) => $widget->id(),
        app(DashboardRegistry::class)->all(),
    );

    expect($ids)->toContain('lnx.account', 'lnx.users', 'lnx.root_tools');
});
