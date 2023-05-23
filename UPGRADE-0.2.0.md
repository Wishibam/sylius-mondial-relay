UPGRADE FROM 0.1.x to 0.2.x
===========================

Configuration [⚠️ breaking change]
--------------

1. The plugin configuration file `wishibam_sylius_mondial_relay.yaml` must be updated this way:

- Before:

```yaml
wishibam_sylius_mondial_relay:
  your_place_code: "11"
  brand_mondial_relay_code: "BDTEST13"
  shipping_code: "24R"
  private_key: "PrivateK"
  language: "FR"
  responsive: true
  map: ~
```

- After:

```yaml
wishibam_sylius_mondial_relay:
  mondial_relay_1:
    your_place_code: "11"
    brand_mondial_relay_code: "BDTEST13"
    shipping_code: "24R"
    private_key: "PrivateK"
    language: "FR"
    responsive: true
    map: ~
  # add more configurations if needed  
  mondial_relay_2:
    your_place_code: "11"
    brand_mondial_relay_code: "BDTEST13"
    shipping_code: "24R"
    private_key: "PrivateK"
    language: "FR"
    responsive: true
    map: ~
```

2. Then in admin, associate the former `mondial-relay` shipping method with the desired configuration.
