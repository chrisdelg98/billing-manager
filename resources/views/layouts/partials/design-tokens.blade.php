<style>
    [x-cloak] {
        display: none !important;
    }

    :root {
        --ui-radius-control: 7px;
    }

    button,
    input[type='button'],
    input[type='submit'],
    input[type='reset'],
    .ui-btn {
        border-radius: var(--ui-radius-control) !important;
    }

    @media (max-width: 1023px) {
        .mobile-filters-shell {
            padding: 0.75rem;
        }

        .mobile-filters-toggle {
            width: 100%;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .mobile-table-compact th,
        .mobile-table-compact td {
            padding-left: 0.65rem;
            padding-right: 0.65rem;
        }

        .mobile-table-compact {
            min-width: 48rem;
        }

        .mobile-table-compact thead th {
            white-space: normal;
            line-height: 1.15;
            font-size: 0.68rem;
        }

        .mobile-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
            max-width: 16rem;
        }

        .mobile-col-main {
            width: 150px;
            min-width: 150px;
            max-width: 150px;
        }

        .mobile-col-100 {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
        }

        .mobile-col-120 {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }

        .mobile-nowrap {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }
</style>
